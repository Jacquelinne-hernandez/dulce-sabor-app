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
    protected $table = 'ordenes';

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
