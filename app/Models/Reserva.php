<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'id_cliente',
        'id_usuario',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'cantidad_personas',
        'total_mesas',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'id_cliente' => 'integer',
        'id_usuario' => 'integer',
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'cantidad_personas' => 'integer',
        'total_mesas' => 'decimal:2',
        'observaciones' => 'string',
        'estado' => 'string',
    ];

    // Relaciones

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Mesas incluidas en la reserva (con la tarifa, las horas y el
    // subtotal que se congelaron al reservar).
    public function mesas(): BelongsToMany
    {
        return $this->belongsToMany(
            Mesa::class,
            'detalle_reservas',
            'id_reserva',
            'id_mesa'
        )
            ->withPivot(['tarifa_hora', 'horas', 'subtotal'])
            ->withTimestamps()
            ->orderBy('mesas.numero');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleReserva::class, 'id_reserva');
    }

    // Pedido generado a partir de la reserva (máximo uno).
    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class, 'id_reserva');
    }

    // Trazabilidad: cambios de estado de esta reserva.
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'id_entidad')
            ->where('entidad', HistorialEstado::RESERVA)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    // Validaciones

    public static function validacionesMesas(): array
    {
        return [
            'id_mesas' => [
                'required',
                'array',
                'min:1',
            ],

            'id_mesas.*' => [
                'required',
                'integer',
                'distinct',
                'exists:mesas,id',
            ],
        ];
    }

    public static function validacionesRango(): array
    {
        return [
            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],

            'hora_fin' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
            ],
        ];
    }

    public static function validacionesHorario(): array
    {
        return array_merge(
            [
                'fecha' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],
            ],
            static::validacionesRango()
        );
    }

    public static function validaciones(): array
    {
        return array_merge(
            [
                // Las reservas requieren un cliente registrado.
                'id_cliente' => [
                    'required',
                    'integer',
                    'exists:clientes,id',
                ],

                'cantidad_personas' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'observaciones' => [
                    'nullable',
                    'string',
                ],
            ],
            static::validacionesMesas(),
            static::validacionesHorario()
        );
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    // Consultas

    public static function mostrarTodos()
    {
        return self::with([
            'cliente',
            'mesas',
            'usuario',
        ])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->get();
    }

    public static function buscarReservaXId($id)
    {
        return self::with([
            'cliente',
            'mesas',
            'usuario',
            'pedido:id,id_reserva,estado',
            'historial.usuario:id,nombre,apellido',
        ])->find($id);
    }

    public static function mostrarPorFecha($fecha)
    {
        return self::with([
            'cliente',
            'mesas',
            'usuario',
        ])
            ->whereDate('fecha', $fecha)
            ->orderBy('hora_inicio')
            ->get();
    }

    public static function mostrarPorEstado($estado)
    {
        return self::with([
            'cliente',
            'mesas',
            'usuario',
        ])
            ->where('estado', $estado)
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }

    // Mesas que se pueden reservar en un horario: no están en
    // mantenimiento y no tienen otra reserva (pendiente o confirmada)
    // que se traslape con ese horario.
    public static function mesasDisponibles($fecha, $horaInicio, $horaFin)
    {
        return Mesa::where('estado', '!=', 'mantenimiento')
            ->whereDoesntHave('reservas', function ($consulta) use ($fecha, $horaInicio, $horaFin) {
                $consulta->whereDate('reservas.fecha', $fecha)
                    ->whereIn('reservas.estado', ['pendiente', 'confirmada'])
                    ->where('reservas.hora_inicio', '<', $horaFin)
                    ->where('reservas.hora_fin', '>', $horaInicio);
            })
            ->orderBy('numero')
            ->get();
    }

    // Cobro por el uso de las mesas

    // Horas que se cobran: la duración se redondea hacia arriba a la
    // media hora (por ejemplo, 61 minutos se cobran como 1.5 horas).
    public static function horasCobradas(string $horaInicio, string $horaFin): float
    {
        [$horaI, $minutoI] = array_map('intval', explode(':', substr($horaInicio, 0, 5)));
        [$horaF, $minutoF] = array_map('intval', explode(':', substr($horaFin, 0, 5)));

        $minutos = ($horaF * 60 + $minutoF) - ($horaI * 60 + $minutoI);

        if ($minutos <= 0) {
            return 0.0;
        }

        return ceil($minutos / 30) / 2;
    }

    // Calcula el cobro de un conjunto de mesas ya cargadas (en la
    // reserva se reciben bloqueadas). Se trabaja en centavos.
    public static function calcularCobroMesas(
        Collection $mesas,
        string $horaInicio,
        string $horaFin
    ): array {
        $horas = static::horasCobradas($horaInicio, $horaFin);
        $mediasHoras = (int) round($horas * 2);

        $lineas = [];
        $totalCentavos = 0;

        foreach ($mesas->sortBy('numero') as $mesa) {

            $tarifaCentavos = (int) round((float) $mesa->tarifa_hora * 100);
            $subtotalCentavos = (int) round($tarifaCentavos * $mediasHoras / 2);

            $totalCentavos += $subtotalCentavos;

            $lineas[] = [
                'id_mesa' => $mesa->id,
                'numero' => $mesa->numero,
                'capacidad' => $mesa->capacidad,
                'tarifa_hora' => number_format($tarifaCentavos / 100, 2, '.', ''),
                'horas' => $horas,
                'subtotal' => number_format($subtotalCentavos / 100, 2, '.', ''),
            ];
        }

        return [
            'horas' => $horas,
            'mesas' => $lineas,
            'total' => number_format($totalCentavos / 100, 2, '.', ''),
        ];
    }

    // Cotización previa a reservar (no guarda nada).
    public static function cotizar(array $idsMesas, string $horaInicio, string $horaFin): array
    {
        $mesas = Mesa::whereIn('id', $idsMesas)->get();

        return static::calcularCobroMesas($mesas, $horaInicio, $horaFin);
    }

    // Validaciones de negocio

    private static function numeros(Collection $mesas): string
    {
        return $mesas->sortBy('numero')->pluck('numero')->implode(', ');
    }

    private static function validarDisponibilidad(
        Collection $mesas,
        array $datos
    ): void {

        // Comprobar que ninguna mesa esté en mantenimiento.
        $enMantenimiento = $mesas->where('estado', 'mantenimiento');

        if ($enMantenimiento->isNotEmpty()) {
            $numeros = static::numeros($enMantenimiento);

            throw ValidationException::withMessages([
                'id_mesas' => [
                    $enMantenimiento->count() === 1
                        ? "La mesa {$numeros} se encuentra en mantenimiento."
                        : "Las mesas {$numeros} se encuentran en mantenimiento.",
                ],
            ]);
        }

        // Comprobar capacidad (suma de todas las mesas reservadas).
        $capacidad = (int) $mesas->sum('capacidad');

        if ($datos['cantidad_personas'] > $capacidad) {
            throw ValidationException::withMessages([
                'cantidad_personas' => [
                    "La cantidad de personas supera la capacidad de las mesas seleccionadas ({$capacidad}).",
                ],
            ]);
        }

        // Comprobar que la reserva no sea en una hora pasada.
        $inicio = Carbon::parse(
            $datos['fecha'] . ' ' . $datos['hora_inicio']
        );

        if ($inicio->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'hora_inicio' => [
                    'La fecha y hora de inicio deben ser futuras.',
                ],
            ]);
        }

        // Obtener el horario correspondiente al día de la semana.
        $diaSemana = Carbon::parse($datos['fecha'])->dayOfWeekIso;

        $horario = Horario::where('dia_semana', $diaSemana)->first();

        if (!$horario || !$horario->activo) {
            throw ValidationException::withMessages([
                'fecha' => [
                    'El establecimiento no atiende en la fecha seleccionada.',
                ],
            ]);
        }

        $horaApertura = Carbon::parse($horario->hora_apertura)
            ->format('H:i');

        $horaCierre = Carbon::parse($horario->hora_cierre)
            ->format('H:i');

        if (
            $datos['hora_inicio'] < $horaApertura ||
            $datos['hora_fin'] > $horaCierre
        ) {
            throw ValidationException::withMessages([
                'hora_inicio' => [
                    "El horario de la reserva se encuentra fuera del horario de atención ({$horaApertura} a {$horaCierre}).",
                ],
            ]);
        }

        // Comprobar superposición con otras reservas, mesa por mesa.
        $conflictos = DetalleReserva::with('mesa')
            ->whereIn('id_mesa', $mesas->pluck('id'))
            ->whereHas('reserva', function ($consulta) use ($datos) {
                $consulta->whereDate('fecha', $datos['fecha'])
                    ->whereIn('estado', ['pendiente', 'confirmada'])
                    ->where('hora_inicio', '<', $datos['hora_fin'])
                    ->where('hora_fin', '>', $datos['hora_inicio']);
            })
            ->get();

        if ($conflictos->isNotEmpty()) {
            $ocupadas = $conflictos->pluck('mesa')->unique('id');
            $numeros = static::numeros($ocupadas);

            throw ValidationException::withMessages([
                'id_mesas' => [
                    $ocupadas->count() === 1
                        ? "La mesa {$numeros} ya tiene una reserva en el horario seleccionado."
                        : "Las mesas {$numeros} ya tienen una reserva en el horario seleccionado.",
                ],
            ]);
        }
    }

    // Registrar reserva

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            $ids = collect($datos['id_mesas'])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            // Bloquear las mesas (siempre en el mismo orden) para evitar
            // reservas simultáneas sobre las mismas mesas.
            $mesas = Mesa::whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($mesas->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'id_mesas' => [
                        'Una o más mesas seleccionadas no existen.',
                    ],
                ]);
            }

            // Las reservas requieren un cliente registrado y activo.
            $cliente = Cliente::whereKey($datos['id_cliente'])
                ->where('id_estado', Estado::ACTIVO)
                ->first();

            if (!$cliente) {
                throw ValidationException::withMessages([
                    'id_cliente' => [
                        'El cliente no existe, se encuentra bloqueado o eliminado.',
                    ],
                ]);
            }

            self::validarDisponibilidad($mesas, $datos);

            $cobro = self::calcularCobroMesas(
                $mesas,
                $datos['hora_inicio'],
                $datos['hora_fin']
            );

            $reserva = self::create([
                'id_cliente' => $cliente->id,
                'id_usuario' => $idUsuario,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'cantidad_personas' => $datos['cantidad_personas'],
                'total_mesas' => $cobro['total'],
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'pendiente',
            ]);

            // Congelar la tarifa y las horas de cada mesa.
            foreach ($cobro['mesas'] as $linea) {
                $reserva->detalles()->create([
                    'id_mesa' => $linea['id_mesa'],
                    'tarifa_hora' => $linea['tarifa_hora'],
                    'horas' => $linea['horas'],
                    'subtotal' => $linea['subtotal'],
                ]);
            }

            HistorialEstado::registrar(
                HistorialEstado::RESERVA,
                $reserva->id,
                null,
                'pendiente',
                $idUsuario,
                'Reserva registrada'
            );

            return $reserva->load([
                'cliente',
                'mesas',
                'usuario',
            ]);
        });
    }

    // Transiciones de estado (manuales)
    //
    // "atendida" no se marca a mano: la fija atender() cuando se registra
    // el pedido de la reserva.

    public static function cambiarEstado($id, string $nuevoEstado, ?int $idUsuario = null)
    {
        $estadosPermitidos = [
            'confirmada',
            'cancelada',
        ];

        Validator::make(
            ['estado' => $nuevoEstado],
            [
                'estado' => [
                    'required',
                    'string',
                    'in:' . implode(',', $estadosPermitidos),
                ],
            ]
        )->validate();

        return DB::transaction(function () use ($id, $nuevoEstado, $idUsuario) {

            $reserva = self::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$reserva) {
                return null;
            }

            $transiciones = [
                'pendiente' => [
                    'confirmada',
                    'cancelada',
                ],
                'confirmada' => [
                    'cancelada',
                ],
                'cancelada' => [],
                'atendida' => [],
            ];

            if (!in_array(
                $nuevoEstado,
                $transiciones[$reserva->estado],
                true
            )) {
                throw ValidationException::withMessages([
                    'estado' => [
                        'No es posible realizar esta transición desde el estado actual.',
                    ],
                ]);
            }

            $estadoAnterior = $reserva->estado;

            $reserva->update([
                'estado' => $nuevoEstado,
            ]);

            HistorialEstado::registrar(
                HistorialEstado::RESERVA,
                $reserva->id,
                $estadoAnterior,
                $nuevoEstado,
                $idUsuario
            );

            return $reserva->fresh([
                'cliente',
                'mesas',
                'usuario',
            ]);
        });
    }

    // Marca la reserva como atendida. Lo usa el registro del pedido de la
    // reserva (Fase 2C): solo se atiende una reserva confirmada y el día
    // programado.

    public static function atender($id, int $idUsuario)
    {
        return DB::transaction(function () use ($id, $idUsuario) {

            $reserva = self::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$reserva) {
                throw ValidationException::withMessages([
                    'id_reserva' => [
                        'La reserva seleccionada no existe.',
                    ],
                ]);
            }

            if ($reserva->estado !== 'confirmada') {
                throw ValidationException::withMessages([
                    'id_reserva' => [
                        'Solo se puede atender una reserva que esté confirmada.',
                    ],
                ]);
            }

            if (!$reserva->fecha->isToday()) {
                throw ValidationException::withMessages([
                    'id_reserva' => [
                        'La reserva solo puede atenderse en la fecha programada.',
                    ],
                ]);
            }

            $reserva->update([
                'estado' => 'atendida',
            ]);

            HistorialEstado::registrar(
                HistorialEstado::RESERVA,
                $reserva->id,
                'confirmada',
                'atendida',
                $idUsuario,
                'Se registró el pedido de la reserva'
            );

            return $reserva->load([
                'cliente',
                'mesas',
                'usuario',
            ]);
        });
    }
}
