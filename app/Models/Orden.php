<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Orden de trabajo: es la parte de producción de un pedido.
class Orden extends Model
{
    // Laravel pluralizaría "Orden" como "ordens".
    // Un encargo programado para más adelante solo pasa a producción desde
    // estas horas antes de la fecha de entrega.
    public const HORAS_ANTICIPACION_PRODUCCION = 5;

    protected $table = 'ordenes';

    // Se incluyen en el JSON (solo si el pedido viene cargado) para que la
    // pantalla sepa si el botón de producción está habilitado.
    protected $appends = [
        'produccion_desde',
        'puede_iniciar',
    ];

    protected $fillable = [
        'id_pedido',
        'id_usuario',
        'numero_orden',
        'estado',
        'fecha_inicio',
        'fecha_lista',
        'fecha_entrega',
        'notas',
    ];

    protected $casts = [
        'id_pedido' => 'integer',
        'id_usuario' => 'integer',
        'numero_orden' => 'string',
        'estado' => 'string',
        'fecha_inicio' => 'datetime',
        'fecha_lista' => 'datetime',
        'fecha_entrega' => 'datetime',
        'notas' => 'string',
    ];

    // Momento desde el cual la orden puede pasar a producción (null si el
    // pedido no es un encargo programado o no está cargado).
    public function getProduccionDesdeAttribute(): ?string
    {
        if (!$this->relationLoaded('pedido') || !$this->pedido?->fecha_programada) {
            return null;
        }

        return $this->pedido->fecha_programada
            ->copy()
            ->subHours(self::HORAS_ANTICIPACION_PRODUCCION)
            ->toIso8601String();
    }

    public function getPuedeIniciarAttribute(): bool
    {
        $desde = $this->produccion_desde;

        return $desde === null || now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($desde));
    }

    // Relaciones

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'id_entidad')
            ->where('entidad', HistorialEstado::ORDEN)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    // Validaciones

    // "cancelada" no se elige aquí: se cancela el pedido y la orden se
    // cancela con él.
    public static function reglasEstado(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                'in:en_produccion,lista,entregada',
            ],
        ];
    }

    // Consultas

    private static function relaciones(): array
    {
        return [
            'pedido.cliente',
            'pedido.mesa',
            'pedido.detalles.producto',
            'pedido.factura:id,id_pedido,numero_factura,estado',
            'usuario',
        ];
    }

    public static function mostrarTodos()
    {
        return self::with(self::relaciones())
            ->orderByDesc('created_at')
            ->get();
    }

    // Órdenes por atender, las más urgentes primero: se ordenan por la
    // fecha programada del pedido o, si no tiene, por la fecha del pedido.
    public static function mostrarActivas()
    {
        return self::with(self::relaciones())
            ->whereIn('estado', ['pendiente', 'en_produccion', 'lista'])
            ->get()
            ->sortBy(fn ($orden) => ($orden->pedido->fecha_programada ?? $orden->pedido->fecha)->timestamp)
            ->values();
    }

    public static function mostrarPorEstado($estado)
    {
        return self::with(self::relaciones())
            ->where('estado', $estado)
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarOrdenXId($id)
    {
        return self::with(array_merge(
            self::relaciones(),
            ['historial.usuario:id,nombre,apellido']
        ))->find($id);
    }

    // Creación (la llama el registro del pedido)

    public static function crearParaPedido(
        Pedido $pedido,
        int $idUsuario,
        bool $entregaInmediata = false
    ): self {
        $ahora = now();

        $orden = self::create([
            'id_pedido' => $pedido->id,
            'id_usuario' => $idUsuario,
            'numero_orden' => Correlativo::siguiente(Correlativo::ORDEN),
            'estado' => $entregaInmediata ? 'entregada' : 'pendiente',
            'fecha_inicio' => $entregaInmediata ? $ahora : null,
            'fecha_lista' => $entregaInmediata ? $ahora : null,
            'fecha_entrega' => $entregaInmediata ? $ahora : null,
            'notas' => $pedido->observaciones,
        ]);

        HistorialEstado::registrar(
            HistorialEstado::ORDEN,
            $orden->id,
            null,
            $orden->estado,
            $idUsuario,
            $entregaInmediata
                ? 'Entrega inmediata (venta de mostrador)'
                : 'Orden generada con el pedido'
        );

        return $orden;
    }

    // Avance de estados

    public static function cambiarEstado($id, string $nuevoEstado, int $idUsuario)
    {
        return DB::transaction(function () use ($id, $nuevoEstado, $idUsuario) {

            $orden = self::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$orden) {
                return null;
            }

            $transiciones = [
                'pendiente' => ['en_produccion'],
                'en_produccion' => ['lista'],
                'lista' => ['entregada'],
                'entregada' => [],
                'cancelada' => [],
            ];

            if (!in_array($nuevoEstado, $transiciones[$orden->estado] ?? [], true)) {
                throw ValidationException::withMessages([
                    'estado' => [
                        'No es posible pasar la orden de "' . $orden->estado
                        . '" a "' . $nuevoEstado . '".',
                    ],
                ]);
            }

            $pedido = Pedido::whereKey($orden->id_pedido)
                ->lockForUpdate()
                ->first();

            if (!$pedido || $pedido->estado !== 'abierto') {
                throw ValidationException::withMessages([
                    'estado' => [
                        'El pedido de esta orden ya no está abierto.',
                    ],
                ]);
            }

            // Los encargos programados se preparan hasta 5 horas antes de
            // la entrega, no antes.
            if ($nuevoEstado === 'en_produccion' && $pedido->fecha_programada) {
                $desde = $pedido->fecha_programada
                    ->copy()
                    ->subHours(self::HORAS_ANTICIPACION_PRODUCCION);

                if (now()->lessThan($desde)) {
                    throw ValidationException::withMessages([
                        'estado' => [
                            'Este encargo está programado para el '
                            . $pedido->fecha_programada->format('d/m/Y H:i')
                            . '. Podrá pasar a producción desde el '
                            . $desde->format('d/m/Y H:i') . ' ('
                            . self::HORAS_ANTICIPACION_PRODUCCION
                            . ' horas antes de la entrega).',
                        ],
                    ]);
                }
            }

            $estadoAnterior = $orden->estado;
            $ahora = now();

            $cambios = ['estado' => $nuevoEstado];

            if ($nuevoEstado === 'en_produccion') {
                $cambios['fecha_inicio'] = $ahora;
            } elseif ($nuevoEstado === 'lista') {
                $cambios['fecha_lista'] = $ahora;
            } elseif ($nuevoEstado === 'entregada') {
                $cambios['fecha_entrega'] = $ahora;
            }

            $orden->update($cambios);

            HistorialEstado::registrar(
                HistorialEstado::ORDEN,
                $orden->id,
                $estadoAnterior,
                $nuevoEstado,
                $idUsuario
            );

            return $orden->load(self::relaciones());
        });
    }
}
