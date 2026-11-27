<?php

namespace App\Models;

use App\Support\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Compra extends Model
{
    // Existencias máximas por producto permitidas al comprar.
    public const STOCK_MAXIMO = 1000;

    protected $table = 'compras';

    protected $fillable = [
        'id_proveedor',
        'id_usuario',
        'fecha_compra',
        'total',
        'condicion_pago',
        'fecha_vencimiento',
        'monto_pagado',
        'estado_pago',
        'estado',
    ];

    protected $casts = [
        'id_proveedor' => 'integer',
        'id_usuario' => 'integer',
        'fecha_compra' => 'datetime',
        'total' => 'decimal:2',
        'condicion_pago' => 'string',
        'fecha_vencimiento' => 'date',
        'monto_pagado' => 'decimal:2',
        'estado_pago' => 'string',
        'estado' => 'string',
    ];

    // Se incluyen siempre en el JSON.
    protected $appends = [
        'saldo',
        'vencida',
    ];

    /*
    |--------------------------------------------------------------------------
    | Atributos calculados
    |--------------------------------------------------------------------------
    */

    // Lo que aún se le debe al proveedor por esta compra.
    public function getSaldoAttribute(): string
    {
        $saldo = Dinero::centavos($this->total) - Dinero::centavos($this->monto_pagado);

        return Dinero::formato(max(0, $saldo));
    }

    // Deuda cuya fecha de vencimiento ya pasó (vence al terminar ese día).
    public function getVencidaAttribute(): bool
    {
        return $this->estado_pago !== 'pagada'
            && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->lt(now()->startOfDay());
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoCompra::class, 'id_compra')
            ->orderBy('fecha_pago')
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
            'id_proveedor' => [
                'required',
                'integer',
                'exists:proveedores,id',
            ],

            'condicion_pago' => [
                'required',
                'string',
                'in:contado,credito',
            ],

            // Solo las compras a crédito tienen vencimiento.
            'fecha_vencimiento' => [
                'required_if:condicion_pago,credito',
                'nullable',
                'date',
                'after_or_equal:today',
                'prohibited_unless:condicion_pago,credito',
            ],

            // Pago inicial opcional de una compra a crédito (se ignora en
            // compras al contado, que se pagan completas).
            'abono_inicial' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'detalles' => [
                'required',
                'array',
                'min:1',
            ],

            'detalles.*.id_producto' => [
                'required',
                'integer',
                'distinct',
                'exists:productos,id',
            ],

            'detalles.*.cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'detalles.*.precio_unitario' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
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

    public static function mostrarTodos()
    {
        return self::with([
            'proveedor',
            'usuario',
            'detalles.producto',
        ])
            ->orderByDesc('fecha_compra')
            ->get();
    }

    public static function buscarCompraXId($id)
    {
        return self::with([
            'proveedor',
            'usuario',
            'detalles.producto',
            'pagos.usuario:id,nombre,apellido',
        ])->find($id);
    }

    // Compras con saldo pendiente, las que vencen primero arriba.
    public static function mostrarDeudas()
    {
        return self::with(['proveedor', 'usuario'])
            ->where('estado_pago', '!=', 'pagada')
            ->orderBy('fecha_vencimiento')
            ->orderBy('fecha_compra')
            ->get();
    }

    // Resumen de lo que se le debe a los proveedores.
    public static function resumenDeudas(): array
    {
        $pendientes = self::with('proveedor:id,nombre')
            ->where('estado_pago', '!=', 'pagada')
            ->get();

        $saldo = fn ($compra) => Dinero::centavos($compra->total) - Dinero::centavos($compra->monto_pagado);

        $porProveedor = $pendientes
            ->groupBy('id_proveedor')
            ->map(function ($compras) use ($saldo) {
                return [
                    'id_proveedor' => $compras->first()->id_proveedor,
                    'proveedor' => $compras->first()->proveedor->nombre,
                    'compras' => $compras->count(),
                    'saldo_centavos' => $compras->sum($saldo),
                    'vencido_centavos' => $compras->filter(fn ($c) => $c->vencida)->sum($saldo),
                ];
            })
            ->sortByDesc('saldo_centavos')
            ->values()
            ->map(fn ($fila) => [
                'id_proveedor' => $fila['id_proveedor'],
                'proveedor' => $fila['proveedor'],
                'compras' => $fila['compras'],
                'saldo' => Dinero::formato($fila['saldo_centavos']),
                'vencido' => Dinero::formato($fila['vencido_centavos']),
            ]);

        return [
            'compras_pendientes' => $pendientes->count(),
            'compras_vencidas' => $pendientes->filter(fn ($c) => $c->vencida)->count(),
            'total_adeudado' => Dinero::formato($pendientes->sum($saldo)),
            'total_vencido' => Dinero::formato(
                $pendientes->filter(fn ($c) => $c->vencida)->sum($saldo)
            ),
            'por_proveedor' => $porProveedor,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Costo promedio ponderado
    |--------------------------------------------------------------------------
    */

    // (existencias x costo actual + compra x precio) / (existencias + compra),
    // en centavos. Sin existencias o sin costo previo, el costo pasa a ser
    // el precio de la compra.
    public static function costoPromedio(
        int $stock,
        int $costoActual,
        int $cantidad,
        int $precioCompra
    ): int {
        if ($stock <= 0 || $costoActual <= 0) {
            return $precioCompra;
        }

        return (int) round(
            (($stock * $costoActual) + ($cantidad * $precioCompra))
            / ($stock + $cantidad)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Registro de compras
    |--------------------------------------------------------------------------
    */

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            $detalles = $datos['detalles'];
            $esCredito = $datos['condicion_pago'] === 'credito';

            // La caja se bloquea primero (siempre en el mismo orden) y la
            // empresa debe haber registrado su capital inicial.
            Caja::bloquear();
            Caja::exigirCapital('condicion_pago', 'registrar compras');

            // Bloquear los productos (siempre en el mismo orden) para evitar
            // modificaciones simultáneas de sus existencias y costos.
            $idsProductos = collect($detalles)
                ->pluck('id_producto')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $productos = Producto::whereIn('id', $idsProductos)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalCentavos = 0;
            $lineas = [];

            foreach ($detalles as $detalle) {

                $producto = $productos->get((int) $detalle['id_producto']);
                $cantidad = (int) $detalle['cantidad'];

                if (!$producto || $producto->estado === 'inactivo') {
                    throw ValidationException::withMessages([
                        'detalles' => [
                            'Uno o más productos no existen o se encuentran inactivos.',
                        ],
                    ]);
                }

                if ((int) $producto->stock + $cantidad > self::STOCK_MAXIMO) {
                    throw ValidationException::withMessages([
                        'detalles' => [
                            "La cantidad supera el límite de existencias permitido para {$producto->nombre} ("
                            . self::STOCK_MAXIMO . ').',
                        ],
                    ]);
                }

                $precio = Dinero::centavos($detalle['precio_unitario']);

                $totalCentavos += $precio * $cantidad;

                $lineas[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                ];
            }

            if ($totalCentavos > Dinero::LIMITE_CENTAVOS) {
                throw ValidationException::withMessages([
                    'detalles' => ['El total de la compra supera el límite permitido.'],
                ]);
            }

            // ---- Condición de pago -------------------------------------

            if ($esCredito) {
                $pagadoInicial = Dinero::centavos($datos['abono_inicial'] ?? 0);

                if ($pagadoInicial >= $totalCentavos) {
                    throw ValidationException::withMessages([
                        'abono_inicial' => [
                            'El abono inicial debe ser menor al total de la compra. '
                            . 'Si se paga completa, registre la compra al contado.',
                        ],
                    ]);
                }
            } else {
                $pagadoInicial = $totalCentavos;
            }

            // Lo que se paga ahora (contado o abono inicial) sale de la caja.
            Caja::exigirFondos(
                $pagadoInicial,
                $esCredito ? 'abono_inicial' : 'condicion_pago',
                'pagar esta compra',
                false
            );

            if ($pagadoInicial >= $totalCentavos) {
                $estadoPago = 'pagada';
            } elseif ($pagadoInicial > 0) {
                $estadoPago = 'parcial';
            } else {
                $estadoPago = 'pendiente';
            }

            // ---- Cabecera, detalles, existencias y costo ----------------

            $compra = self::create([
                'id_proveedor' => $datos['id_proveedor'],
                'id_usuario' => $idUsuario,
                'fecha_compra' => now(),
                'total' => Dinero::formato($totalCentavos),
                'condicion_pago' => $datos['condicion_pago'],
                'fecha_vencimiento' => $esCredito ? $datos['fecha_vencimiento'] : null,
                'monto_pagado' => Dinero::formato($pagadoInicial),
                'estado_pago' => $estadoPago,
                'estado' => 'completada',
            ]);

            foreach ($lineas as $linea) {

                $producto = $linea['producto'];

                $compra->detalles()->create([
                    'id_producto' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => Dinero::formato($linea['precio']),
                    'subtotal' => Dinero::formato($linea['precio'] * $linea['cantidad']),
                ]);

                // Costo promedio ponderado, calculado con las existencias
                // anteriores a esta compra.
                $producto->costo = Dinero::formato(
                    self::costoPromedio(
                        (int) $producto->stock,
                        Dinero::centavos($producto->costo),
                        $linea['cantidad'],
                        $linea['precio']
                    )
                );

                $producto->stock = (int) $producto->stock + $linea['cantidad'];
                $producto->estado = 'disponible';

                $producto->save();
            }

            // ---- Pago registrado (contado o abono inicial) --------------

            if ($pagadoInicial > 0) {
                PagoCompra::create([
                    'id_compra' => $compra->id,
                    'id_usuario' => $idUsuario,
                    'monto' => Dinero::formato($pagadoInicial),
                    'fecha_pago' => now(),
                    'observaciones' => $esCredito ? 'Abono inicial' : 'Pago al contado',
                ]);
            }

            return $compra->load([
                'proveedor',
                'usuario',
                'detalles.producto',
                'pagos',
            ]);
        });
    }
}
