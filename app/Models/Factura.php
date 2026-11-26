<?php

namespace App\Models;

use App\Support\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Factura extends Model
{
    // ---- Reglas de negocio (ajustables) --------------------------------

    // Puntos que gana el cliente por cada UNIDAD de producto comprada
    // (el cargo por el uso de mesas no suma puntos).
    public const PUNTOS_POR_UNIDAD = 1;

    // Valor de cada punto al canjearlo, en centavos ($0.10).
    public const VALOR_PUNTO_CENTAVOS = 10;

    // Máximo del total que se puede cubrir con puntos.
    public const DESCUENTO_MAXIMO_PORCENTAJE = 50;

    // El IVA ya está incluido en los precios.
    public const IVA_PORCENTAJE = 13;

    protected $table = 'facturas';

    protected $fillable = [
        'id_pedido',
        'id_cliente',
        'id_usuario',
        'numero_factura',
        'fecha_emision',
        'subtotal',
        'cargo_mesas',
        'descuento',
        'puntos_canjeados',
        'puntos_ganados',
        'total',
        'base_imponible',
        'iva',
        'estado',
    ];

    protected $casts = [
        'id_pedido' => 'integer',
        'id_cliente' => 'integer',
        'id_usuario' => 'integer',
        'numero_factura' => 'string',
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:2',
        'cargo_mesas' => 'decimal:2',
        'descuento' => 'decimal:2',
        'puntos_canjeados' => 'integer',
        'puntos_ganados' => 'integer',
        'total' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'iva' => 'decimal:2',
        'estado' => 'string',
    ];

    // Saldo pendiente de pago, incluido siempre en el JSON.
    protected $appends = [
        'saldo',
    ];

    /*
    |--------------------------------------------------------------------------
    | Atributos calculados
    |--------------------------------------------------------------------------
    */

    // Usa la suma precargada (total_pagado) cuando existe; si no, la consulta.
    public function getSaldoAttribute(): string
    {
        if ($this->estado === 'anulada') {
            return Dinero::formato(0);
        }

        $pagado = array_key_exists('total_pagado', $this->attributes)
            ? $this->attributes['total_pagado']
            : $this->pagos()->where('estado', 'aplicado')->sum('monto');

        $saldo = Dinero::centavos($this->total) - Dinero::centavos($pagado ?? 0);

        return Dinero::formato(max(0, $saldo));
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleFactura::class, 'id_factura');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_factura')
            ->orderBy('fecha_pago')
            ->orderBy('id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'id_entidad')
            ->where('entidad', HistorialEstado::FACTURA)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones(): array
    {
        return [
            'id_pedido' => [
                'required',
                'integer',
                'exists:pedidos,id',
            ],

            // Puntos del cliente que se canjean como descuento.
            'puntos_canjeados' => [
                'nullable',
                'integer',
                'min:0',
                'max:100000000',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    private static function consulta()
    {
        return self::with([
            'pedido',
            'cliente',
            'usuario',
            'detalles.producto',
            'pagos',
        ])->withSum(
            ['pagos as total_pagado' => fn ($q) => $q->where('estado', 'aplicado')],
            'monto'
        );
    }

    public static function mostrarTodos()
    {
        return self::consulta()
            ->orderByDesc('fecha_emision')
            ->get();
    }

    public static function buscarFacturaXId($id)
    {
        return self::consulta()
            ->with('historial.usuario:id,nombre,apellido')
            ->find($id);
    }

    public static function buscarFacturaXPedido($idPedido)
    {
        return self::consulta()
            ->where('id_pedido', $idPedido)
            ->first();
    }

    public static function mostrarPorEstado($estado)
    {
        return self::consulta()
            ->where('estado', $estado)
            ->orderByDesc('fecha_emision')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Cálculo de la factura
    |--------------------------------------------------------------------------
    */

    private static function validarFacturable(Pedido $pedido): void
    {
        if ($pedido->estado !== 'abierto') {
            throw ValidationException::withMessages([
                'id_pedido' => [
                    $pedido->estado === 'completado'
                        ? 'Este pedido ya fue pagado.'
                        : 'No se puede facturar un pedido cancelado.',
                ],
            ]);
        }

        if (!$pedido->orden || $pedido->orden->estado !== 'entregada') {
            throw ValidationException::withMessages([
                'id_pedido' => [
                    'La orden de trabajo debe estar entregada antes de facturar.',
                ],
            ]);
        }

        if (self::where('id_pedido', $pedido->id)->exists()) {
            throw ValidationException::withMessages([
                'id_pedido' => ['Este pedido ya cuenta con una factura.'],
            ]);
        }

        if ($pedido->detalles->isEmpty()) {
            throw ValidationException::withMessages([
                'id_pedido' => ['El pedido no contiene productos para facturar.'],
            ]);
        }
    }

    // Totales en centavos. Valida el canje de puntos.
    private static function calcular(
        Pedido $pedido,
        ?Cliente $cliente,
        int $puntosSolicitados
    ): array {

        $subtotal = Dinero::centavos($pedido->subtotal);
        $cargo = Dinero::centavos($pedido->cargo_mesas);
        $bruto = $subtotal + $cargo;

        $unidades = (int) $pedido->detalles->sum('cantidad');

        $puntosDisponibles = $cliente ? (int) $cliente->puntos : 0;

        // Tope del descuento: un porcentaje del total, redondeado hacia
        // abajo, expresado en puntos enteros.
        $descuentoMaximo = intdiv($bruto * self::DESCUENTO_MAXIMO_PORCENTAJE, 100);

        $puntosMaximos = min(
            $puntosDisponibles,
            intdiv($descuentoMaximo, self::VALOR_PUNTO_CENTAVOS)
        );

        if ($puntosSolicitados > 0) {

            if (!$cliente) {
                throw ValidationException::withMessages([
                    'puntos_canjeados' => [
                        'Solo los clientes registrados pueden canjear puntos.',
                    ],
                ]);
            }

            if ($cliente->id_estado !== Estado::ACTIVO) {
                throw ValidationException::withMessages([
                    'puntos_canjeados' => [
                        'El cliente se encuentra bloqueado y no puede canjear puntos.',
                    ],
                ]);
            }

            if ($puntosSolicitados > $puntosDisponibles) {
                throw ValidationException::withMessages([
                    'puntos_canjeados' => [
                        "El cliente solo tiene {$puntosDisponibles} puntos disponibles.",
                    ],
                ]);
            }

            if ($puntosSolicitados > $puntosMaximos) {
                throw ValidationException::withMessages([
                    'puntos_canjeados' => [
                        "En esta factura se pueden canjear hasta {$puntosMaximos} puntos "
                        . '(máximo ' . self::DESCUENTO_MAXIMO_PORCENTAJE . '% del total).',
                    ],
                ]);
            }
        }

        $descuento = $puntosSolicitados * self::VALOR_PUNTO_CENTAVOS;
        $total = $bruto - $descuento;

        // IVA incluido: el total contiene la base imponible y el impuesto.
        $base = (int) round($total * 100 / (100 + self::IVA_PORCENTAJE));
        $iva = $total - $base;

        return [
            'subtotal' => $subtotal,
            'cargo_mesas' => $cargo,
            'bruto' => $bruto,
            'descuento' => $descuento,
            'total' => $total,
            'base_imponible' => $base,
            'iva' => $iva,
            'puntos_disponibles' => $puntosDisponibles,
            'puntos_maximos' => $puntosMaximos,
            'puntos_canjeados' => $puntosSolicitados,
            'puntos_ganados' => $cliente ? $unidades * self::PUNTOS_POR_UNIDAD : 0,
        ];
    }

    // Vista previa de la factura (no guarda nada): sirve para mostrar al
    // operador cuántos puntos puede canjear y cuánto quedaría por pagar.
    public static function cotizar(int $idPedido, int $puntos = 0): array
    {
        $pedido = Pedido::with(['orden', 'detalles', 'cliente'])->find($idPedido);

        if (!$pedido) {
            throw ValidationException::withMessages([
                'id_pedido' => ['El pedido seleccionado no existe.'],
            ]);
        }

        self::validarFacturable($pedido);

        $c = self::calcular($pedido, $pedido->cliente, $puntos);

        return [
            'subtotal' => Dinero::formato($c['subtotal']),
            'cargo_mesas' => Dinero::formato($c['cargo_mesas']),
            'descuento' => Dinero::formato($c['descuento']),
            'total' => Dinero::formato($c['total']),
            'base_imponible' => Dinero::formato($c['base_imponible']),
            'iva' => Dinero::formato($c['iva']),
            'iva_porcentaje' => self::IVA_PORCENTAJE,
            'puntos_disponibles' => $c['puntos_disponibles'],
            'puntos_maximos' => $c['puntos_maximos'],
            'puntos_canjeados' => $c['puntos_canjeados'],
            'puntos_ganados' => $c['puntos_ganados'],
            'valor_punto' => Dinero::formato(self::VALOR_PUNTO_CENTAVOS),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Emisión
    |--------------------------------------------------------------------------
    */

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            // Bloquear el pedido evita facturaciones simultáneas.
            $pedido = Pedido::whereKey($datos['id_pedido'])
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                throw ValidationException::withMessages([
                    'id_pedido' => ['El pedido seleccionado no existe.'],
                ]);
            }

            $pedido->load(['orden', 'detalles.producto']);

            self::validarFacturable($pedido);

            $puntos = (int) ($datos['puntos_canjeados'] ?? 0);

            $cliente = $pedido->id_cliente
                ? Cliente::whereKey($pedido->id_cliente)->lockForUpdate()->first()
                : null;

            $c = self::calcular($pedido, $cliente, $puntos);

            $factura = self::create([
                'id_pedido' => $pedido->id,
                'id_cliente' => $pedido->id_cliente,
                'id_usuario' => $idUsuario,
                'numero_factura' => Correlativo::siguiente(Correlativo::FACTURA),
                'fecha_emision' => now(),
                'subtotal' => Dinero::formato($c['subtotal']),
                'cargo_mesas' => Dinero::formato($c['cargo_mesas']),
                'descuento' => Dinero::formato($c['descuento']),
                'puntos_canjeados' => $c['puntos_canjeados'],
                'puntos_ganados' => $c['puntos_ganados'],
                'total' => Dinero::formato($c['total']),
                'base_imponible' => Dinero::formato($c['base_imponible']),
                'iva' => Dinero::formato($c['iva']),
                'estado' => 'emitida',
            ]);

            foreach ($pedido->detalles as $detalle) {
                $factura->detalles()->create([
                    'id_producto' => $detalle->id_producto,
                    'nombre_producto' => $detalle->producto->nombre,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                ]);
            }

            // Los puntos canjeados se descuentan al emitir; los ganados se
            // acreditan cuando la factura queda pagada.
            if ($c['puntos_canjeados'] > 0) {
                $cliente->update([
                    'puntos' => $cliente->puntos - $c['puntos_canjeados'],
                ]);
            }

            HistorialEstado::registrar(
                HistorialEstado::FACTURA,
                $factura->id,
                null,
                'emitida',
                $idUsuario,
                'Factura emitida'
            );

            return self::buscarFacturaXId($factura->id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Anulación
    |--------------------------------------------------------------------------
    */

    // Anulación (administrador): la venta se deshace por completo. Se
    // revierten pagos y puntos, se restituyen las existencias y se cancela
    // el pedido con su orden.
    public static function anular($id, int $idUsuario, ?string $motivo = null)
    {
        return DB::transaction(function () use ($id, $idUsuario, $motivo) {

            $factura = self::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$factura) {
                return null;
            }

            if ($factura->estado === 'anulada') {
                throw ValidationException::withMessages([
                    'estado' => ['La factura ya se encuentra anulada.'],
                ]);
            }

            $pedido = Pedido::whereKey($factura->id_pedido)
                ->lockForUpdate()
                ->first();

            self::anularInterno($factura, $idUsuario, $motivo);

            if ($pedido && $pedido->estado !== 'cancelado') {
                Pedido::cancelarInterno($pedido, $idUsuario, $motivo);
            }

            return self::buscarFacturaXId($factura->id);
        });
    }

    // Anula una factura ya bloqueada: pagos, puntos y estado. Debe
    // llamarse dentro de una transacción.
    public static function anularInterno(
        Factura $factura,
        ?int $idUsuario,
        ?string $motivo = null
    ): void {

        $estadoAnterior = $factura->estado;

        Pago::where('id_factura', $factura->id)
            ->where('estado', 'aplicado')
            ->update(['estado' => 'anulado']);

        // Se devuelven los puntos canjeados y, si la factura ya estaba
        // pagada, se retiran los que había otorgado.
        if ($factura->id_cliente) {

            $cliente = Cliente::whereKey($factura->id_cliente)
                ->lockForUpdate()
                ->first();

            if ($cliente) {
                $puntos = $cliente->puntos + $factura->puntos_canjeados;

                if ($estadoAnterior === 'pagada') {
                    $puntos = max(0, $puntos - $factura->puntos_ganados);
                }

                $cliente->update(['puntos' => $puntos]);
            }
        }

        $factura->update(['estado' => 'anulada']);

        HistorialEstado::registrar(
            HistorialEstado::FACTURA,
            $factura->id,
            $estadoAnterior,
            'anulada',
            $idUsuario,
            $motivo
        );
    }
}
