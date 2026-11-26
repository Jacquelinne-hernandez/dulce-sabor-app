<?php

namespace App\Models;

use App\Support\Dinero;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Pedido extends Model
{
    // Pedidos con fecha programada: anticipación mínima y máxima.
    public const MINUTOS_ANTICIPACION = 30;
    public const DIAS_MAXIMOS_PROGRAMACION = 60;

    // Una mesa con una reserva que empieza dentro de este margen (o que ya
    // está en curso) no se ofrece para consumo en el local.
    public const MARGEN_RESERVA_MINUTOS = 60;

    protected $table = 'pedidos';

    protected $fillable = [
        'id_cliente',
        'id_mesa',
        'id_reserva',
        'id_usuario',
        'tipo',
        'fecha',
        'fecha_programada',
        'subtotal',
        'cargo_mesas',
        'total',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'id_cliente' => 'integer',
        'id_mesa' => 'integer',
        'id_reserva' => 'integer',
        'id_usuario' => 'integer',
        'tipo' => 'string',
        'fecha' => 'datetime',
        'fecha_programada' => 'datetime',
        'subtotal' => 'decimal:2',
        'cargo_mesas' => 'decimal:2',
        'total' => 'decimal:2',
        'estado' => 'string',
        'observaciones' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'id_mesa');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_pedido');
    }

    public function orden(): HasOne
    {
        return $this->hasOne(Orden::class, 'id_pedido');
    }

    public function factura(): HasOne
    {
        return $this->hasOne(Factura::class, 'id_pedido');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'id_entidad')
            ->where('entidad', HistorialEstado::PEDIDO)
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
            'tipo' => [
                'required',
                'string',
                'in:local,llevar,reserva',
            ],

            // Sin cliente es "consumidor final".
            'id_cliente' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],

            // Solo consumo en el local.
            'id_mesa' => [
                'required_if:tipo,local',
                'nullable',
                'integer',
                'exists:mesas,id',
                'prohibited_unless:tipo,local',
            ],

            // Solo pedidos de una reserva.
            'id_reserva' => [
                'required_if:tipo,reserva',
                'nullable',
                'integer',
                'exists:reservas,id',
                'prohibited_unless:tipo,reserva',
            ],

            // Solo pedidos para llevar. En los de reserva la fecha la
            // fija la propia reserva.
            'fecha_programada' => [
                'nullable',
                'date',
                'prohibited_unless:tipo,llevar',
            ],

            'entrega_inmediata' => [
                'nullable',
                'boolean',
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
                'max:10000',
            ],

            'observaciones' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    // Un pedido solo se puede cancelar; pasa a "completado" por sí solo
    // cuando se paga su factura.
    public static function reglasEstado(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                'in:cancelado',
            ],

            'motivo' => [
                'nullable',
                'string',
                'max:200',
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

    private static function relaciones(): array
    {
        return [
            'cliente',
            'mesa',
            'reserva.mesas',
            'usuario',
            'orden',
            'factura',
            'detalles.producto',
        ];
    }

    public static function mostrarTodos()
    {
        return self::with(self::relaciones())
            ->orderByDesc('fecha')
            ->get();
    }

    public static function buscarPedidoXId($id)
    {
        return self::with(array_merge(
            self::relaciones(),
            ['factura.pagos', 'historial.usuario:id,nombre,apellido']
        ))->find($id);
    }

    public static function mostrarPorFecha($fecha)
    {
        return self::with(self::relaciones())
            ->whereDate('fecha', $fecha)
            ->orderByDesc('fecha')
            ->get();
    }

    public static function mostrarPorEstado($estado)
    {
        return self::with(self::relaciones())
            ->where('estado', $estado)
            ->orderByDesc('fecha')
            ->get();
    }

    // Pedidos listos para facturar: abiertos, con la orden entregada y
    // sin factura.
    public static function porFacturar()
    {
        return self::with(['cliente', 'mesa', 'orden'])
            ->where('estado', 'abierto')
            ->whereHas('orden', fn ($q) => $q->where('estado', 'entregada'))
            ->whereDoesntHave('factura')
            ->orderByDesc('fecha')
            ->get();
    }

    // Reservas confirmadas para hoy que todavía no tienen pedido.
    public static function reservasPorAtender()
    {
        return Reserva::with(['cliente', 'mesas'])
            ->where('estado', 'confirmada')
            ->whereDate('fecha', now()->toDateString())
            ->whereDoesntHave('pedido')
            ->orderBy('hora_inicio')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones de negocio
    |--------------------------------------------------------------------------
    */

    // La entrega programada debe tener anticipación, no ser demasiado
    // lejana y caer dentro del horario de atención de ese día.
    private static function validarFechaProgramada(Carbon $fecha): void
    {
        if ($fecha->lessThan(now()->addMinutes(self::MINUTOS_ANTICIPACION))) {
            throw ValidationException::withMessages([
                'fecha_programada' => [
                    'La entrega debe programarse con al menos '
                    . self::MINUTOS_ANTICIPACION . ' minutos de anticipación.',
                ],
            ]);
        }

        if ($fecha->greaterThan(now()->addDays(self::DIAS_MAXIMOS_PROGRAMACION))) {
            throw ValidationException::withMessages([
                'fecha_programada' => [
                    'No se pueden programar pedidos con más de '
                    . self::DIAS_MAXIMOS_PROGRAMACION . ' días de anticipación.',
                ],
            ]);
        }

        $horario = Horario::where('dia_semana', $fecha->dayOfWeekIso)->first();

        if (!$horario || !$horario->activo) {
            throw ValidationException::withMessages([
                'fecha_programada' => [
                    'El establecimiento no atiende en la fecha programada.',
                ],
            ]);
        }

        $apertura = Carbon::parse($horario->hora_apertura)->format('H:i');
        $cierre = Carbon::parse($horario->hora_cierre)->format('H:i');
        $hora = $fecha->format('H:i');

        if ($hora < $apertura || $hora > $cierre) {
            throw ValidationException::withMessages([
                'fecha_programada' => [
                    "La hora programada está fuera del horario de atención ({$apertura} a {$cierre}).",
                ],
            ]);
        }
    }

    // La mesa debe poder usarse para consumo en el local (sin hora de
    // término): se permite una mesa ocupada solo si lo está por otro pedido
    // local abierto (el cliente pidió algo más).
    private static function validarMesaParaConsumo(Mesa $mesa): void
    {
        if ($mesa->estado === 'mantenimiento') {
            throw ValidationException::withMessages([
                'id_mesa' => ["La mesa {$mesa->numero} se encuentra en mantenimiento."],
            ]);
        }

        if ($mesa->estado === 'reservada') {
            throw ValidationException::withMessages([
                'id_mesa' => ["La mesa {$mesa->numero} está marcada como reservada."],
            ]);
        }

        if ($mesa->estado === 'ocupada') {
            $conPedidoLocal = self::where('id_mesa', $mesa->id)
                ->where('tipo', 'local')
                ->where('estado', 'abierto')
                ->exists();

            if (!$conPedidoLocal) {
                throw ValidationException::withMessages([
                    'id_mesa' => ["La mesa {$mesa->numero} se encuentra ocupada."],
                ]);
            }
        }

        // No sentar clientes en una mesa que está por reservarse (o ya
        // reservada y en curso).
        $limite = now()->addMinutes(self::MARGEN_RESERVA_MINUTOS);

        if (!$limite->isSameDay(now())) {
            $limite = now()->endOfDay();
        }

        $choque = $mesa->reservas()
            ->whereDate('reservas.fecha', now()->toDateString())
            ->whereIn('reservas.estado', ['pendiente', 'confirmada'])
            ->where('reservas.hora_inicio', '<', $limite->format('H:i:s'))
            ->where('reservas.hora_fin', '>', now()->format('H:i:s'))
            ->exists();

        if ($choque) {
            throw ValidationException::withMessages([
                'id_mesa' => [
                    "La mesa {$mesa->numero} tiene una reserva en curso o por comenzar. "
                    . 'Elija otra mesa o cancele la reserva.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Registro de pedidos
    |--------------------------------------------------------------------------
    */

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            $tipo = $datos['tipo'];
            $entregaInmediata = (bool) ($datos['entrega_inmediata'] ?? false);

            if ($entregaInmediata && ($tipo === 'reserva' || !empty($datos['fecha_programada']))) {
                throw ValidationException::withMessages([
                    'entrega_inmediata' => [
                        'La entrega inmediata no aplica a pedidos programados ni de reservas.',
                    ],
                ]);
            }

            $idCliente = $datos['id_cliente'] ?? null;
            $mesasAOcupar = collect();
            $cargoCentavos = 0;
            $fechaProgramada = null;
            $reserva = null;

            // ---- Según el tipo de pedido -------------------------------

            if ($tipo === 'reserva') {

                $reserva = Reserva::whereKey($datos['id_reserva'])
                    ->lockForUpdate()
                    ->first();

                if (!$reserva) {
                    throw ValidationException::withMessages([
                        'id_reserva' => ['La reserva seleccionada no existe.'],
                    ]);
                }

                if (self::where('id_reserva', $reserva->id)->exists()) {
                    throw ValidationException::withMessages([
                        'id_reserva' => ['Esta reserva ya tiene un pedido.'],
                    ]);
                }

                // Valida que esté confirmada y sea de hoy, y la marca como
                // atendida (si algo falla más abajo, todo se revierte).
                Reserva::atender($reserva->id, $idUsuario);

                $idCliente = $reserva->id_cliente;
                $cargoCentavos = Dinero::centavos($reserva->total_mesas);

                $fechaProgramada = Carbon::parse(
                    $reserva->fecha->format('Y-m-d') . ' ' . $reserva->hora_inicio
                );

                $idsMesas = DetalleReserva::where('id_reserva', $reserva->id)
                    ->pluck('id_mesa');

                $mesasAOcupar = Mesa::whereIn('id', $idsMesas)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $noDisponibles = $mesasAOcupar
                    ->filter(fn ($m) => in_array($m->estado, ['mantenimiento', 'ocupada'], true));

                if ($noDisponibles->isNotEmpty()) {
                    $numeros = $noDisponibles->sortBy('numero')->pluck('numero')->implode(', ');

                    throw ValidationException::withMessages([
                        'id_reserva' => [
                            "Las mesas de la reserva no están disponibles en este momento ({$numeros}).",
                        ],
                    ]);
                }
            }

            if ($tipo === 'local') {

                $mesa = Mesa::whereKey($datos['id_mesa'])
                    ->lockForUpdate()
                    ->first();

                if (!$mesa) {
                    throw ValidationException::withMessages([
                        'id_mesa' => ['La mesa seleccionada no existe.'],
                    ]);
                }

                self::validarMesaParaConsumo($mesa);

                $mesasAOcupar = collect([$mesa]);
            }

            if ($tipo === 'llevar' && !empty($datos['fecha_programada'])) {
                $fechaProgramada = Carbon::parse($datos['fecha_programada']);

                self::validarFechaProgramada($fechaProgramada);
            }

            // ---- Cliente (opcional) -----------------------------------

            if ($tipo !== 'reserva' && $idCliente) {
                $cliente = Cliente::whereKey($idCliente)
                    ->where('id_estado', Estado::ACTIVO)
                    ->first();

                if (!$cliente) {
                    throw ValidationException::withMessages([
                        'id_cliente' => [
                            'El cliente no existe, se encuentra bloqueado o eliminado.',
                        ],
                    ]);
                }
            }

            // ---- Productos ---------------------------------------------

            // Bloquear los productos siempre en el mismo orden para
            // reducir interbloqueos.
            $idsProductos = collect($datos['detalles'])
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

            $subtotalCentavos = 0;
            $lineas = [];

            foreach ($datos['detalles'] as $detalle) {

                $producto = $productos->get((int) $detalle['id_producto']);
                $cantidad = (int) $detalle['cantidad'];

                if (!$producto || $producto->estado !== 'disponible') {
                    throw ValidationException::withMessages([
                        'detalles' => ['Uno de los productos seleccionados no está disponible.'],
                    ]);
                }

                if ($producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'detalles' => ["Existencias insuficientes para el producto {$producto->nombre}."],
                    ]);
                }

                $precio = Dinero::centavos($producto->precio);
                $costo = Dinero::centavos($producto->costo);

                $subtotalCentavos += $precio * $cantidad;

                $lineas[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'costo' => $costo,
                ];
            }

            $totalCentavos = $subtotalCentavos + $cargoCentavos;

            if ($totalCentavos > Dinero::LIMITE_CENTAVOS) {
                throw ValidationException::withMessages([
                    'detalles' => ['El total del pedido supera el límite permitido.'],
                ]);
            }

            // ---- Pedido, detalles y existencias ------------------------

            $pedido = self::create([
                'id_cliente' => $idCliente,
                'id_mesa' => $tipo === 'local' ? $datos['id_mesa'] : null,
                'id_reserva' => $reserva?->id,
                'id_usuario' => $idUsuario,
                'tipo' => $tipo,
                'fecha' => now(),
                'fecha_programada' => $fechaProgramada,
                'subtotal' => Dinero::formato($subtotalCentavos),
                'cargo_mesas' => Dinero::formato($cargoCentavos),
                'total' => Dinero::formato($totalCentavos),
                'estado' => 'abierto',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            foreach ($lineas as $linea) {

                $producto = $linea['producto'];

                $pedido->detalles()->create([
                    'id_producto' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => Dinero::formato($linea['precio']),
                    'costo_unitario' => Dinero::formato($linea['costo']),
                    'subtotal' => Dinero::formato($linea['precio'] * $linea['cantidad']),
                ]);

                $producto->stock -= $linea['cantidad'];

                if ($producto->stock === 0) {
                    $producto->estado = 'agotado';
                }

                $producto->save();
            }

            // ---- Trazabilidad, orden de trabajo y mesas -----------------

            HistorialEstado::registrar(
                HistorialEstado::PEDIDO,
                $pedido->id,
                null,
                'abierto',
                $idUsuario,
                'Pedido registrado (' . $tipo . ')'
            );

            Orden::crearParaPedido($pedido, $idUsuario, $entregaInmediata);

            foreach ($mesasAOcupar as $mesa) {
                $mesa->update(['estado' => 'ocupada']);
            }

            return $pedido->load(self::relaciones());
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelación
    |--------------------------------------------------------------------------
    */

    public static function cancelar($id, int $idUsuario, ?string $motivo = null)
    {
        return DB::transaction(function () use ($id, $idUsuario, $motivo) {

            $pedido = self::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                return null;
            }

            if ($pedido->estado === 'completado') {
                throw ValidationException::withMessages([
                    'estado' => [
                        'El pedido ya fue pagado. Solicite a un administrador anular la factura.',
                    ],
                ]);
            }

            if ($pedido->estado !== 'abierto') {
                throw ValidationException::withMessages([
                    'estado' => ['El pedido ya se encuentra cancelado.'],
                ]);
            }

            // Si ya se emitió una factura sin pagos, se anula con el pedido.
            $factura = Factura::where('id_pedido', $pedido->id)
                ->lockForUpdate()
                ->first();

            if ($factura && $factura->estado !== 'anulada') {

                $conPagos = Pago::where('id_factura', $factura->id)
                    ->where('estado', 'aplicado')
                    ->exists();

                if ($conPagos) {
                    throw ValidationException::withMessages([
                        'estado' => [
                            'El pedido tiene pagos registrados. Solicite a un administrador anular la factura.',
                        ],
                    ]);
                }

                Factura::anularInterno(
                    $factura,
                    $idUsuario,
                    'Se canceló el pedido'
                );
            }

            self::cancelarInterno($pedido, $idUsuario, $motivo);

            return $pedido->load(self::relaciones());
        });
    }

    // Cancela un pedido ya bloqueado: restituye existencias, cancela su
    // orden de trabajo y libera las mesas. También la usa la anulación de
    // facturas. Debe llamarse dentro de una transacción.
    public static function cancelarInterno(
        Pedido $pedido,
        ?int $idUsuario,
        ?string $motivo = null
    ): void {

        $estadoAnterior = $pedido->estado;

        // Restituir las existencias.
        $detalles = $pedido->detalles()
            ->orderBy('id_producto')
            ->get();

        $productos = Producto::whereIn('id', $detalles->pluck('id_producto')->unique())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($detalles as $detalle) {

            $producto = $productos->get($detalle->id_producto);

            if (!$producto) {
                throw ValidationException::withMessages([
                    'estado' => ['No se pudo restituir el inventario de uno de los productos.'],
                ]);
            }

            $nuevoStock = $producto->stock + $detalle->cantidad;

            // Límite de UNSIGNED INT en MySQL.
            if ($nuevoStock > 4294967295) {
                throw ValidationException::withMessages([
                    'estado' => ['La restitución supera el límite de existencias permitido.'],
                ]);
            }

            $producto->stock = $nuevoStock;

            if ($producto->estado !== 'inactivo') {
                $producto->estado = 'disponible';
            }

            $producto->save();
        }

        $pedido->update(['estado' => 'cancelado']);

        HistorialEstado::registrar(
            HistorialEstado::PEDIDO,
            $pedido->id,
            $estadoAnterior,
            'cancelado',
            $idUsuario,
            $motivo
        );

        // La orden de trabajo se cancela con el pedido.
        $orden = Orden::where('id_pedido', $pedido->id)
            ->lockForUpdate()
            ->first();

        if ($orden && $orden->estado !== 'cancelada') {

            $estadoOrden = $orden->estado;

            $orden->update(['estado' => 'cancelada']);

            HistorialEstado::registrar(
                HistorialEstado::ORDEN,
                $orden->id,
                $estadoOrden,
                'cancelada',
                $idUsuario,
                'Se canceló el pedido'
            );
        }

        self::liberarMesas($pedido);
    }

    /*
    |--------------------------------------------------------------------------
    | Liberación de mesas
    |--------------------------------------------------------------------------
    */

    // Libera las mesas que ocupaba el pedido (al pagarse o cancelarse).
    // En consumo local, la mesa sigue ocupada si tiene otro pedido local
    // abierto. Debe llamarse dentro de una transacción.
    public static function liberarMesas(Pedido $pedido): void
    {
        $ids = collect();

        if ($pedido->tipo === 'local' && $pedido->id_mesa) {

            $hayOtros = self::where('id_mesa', $pedido->id_mesa)
                ->where('tipo', 'local')
                ->where('estado', 'abierto')
                ->where('id', '!=', $pedido->id)
                ->exists();

            if (!$hayOtros) {
                $ids->push($pedido->id_mesa);
            }
        }

        if ($pedido->tipo === 'reserva' && $pedido->id_reserva) {
            $ids = DetalleReserva::where('id_reserva', $pedido->id_reserva)
                ->pluck('id_mesa');
        }

        if ($ids->isEmpty()) {
            return;
        }

        Mesa::whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->each(function (Mesa $mesa) {
                if ($mesa->estado === 'ocupada') {
                    $mesa->update(['estado' => 'disponible']);
                }
            });
    }
}
