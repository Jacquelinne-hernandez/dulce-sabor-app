<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'id_cliente',
        'id_mesa',
        'id_usuario',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'cantidad_personas',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'id_cliente' => 'integer',
        'id_mesa' => 'integer',
        'id_usuario' => 'integer',
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'cantidad_personas' => 'integer',
        'observaciones' => 'string',
        'estado' => 'string',
    ];

    // Relaciones

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'id_mesa');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],

            'id_mesa' => [
                'required',
                'integer',
                'exists:mesas,id',
            ],

            'fecha' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],

            'hora_fin' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
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
        ];
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
            'mesa',
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
            'mesa',
            'usuario',
        ])->find($id);
    }

    public static function mostrarPorFecha($fecha)
    {
        return self::with([
            'cliente',
            'mesa',
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
            'mesa',
            'usuario',
        ])
            ->where('estado', $estado)
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }

    // Validaciones de negocio

    private static function validarDisponibilidad(
        Mesa $mesa,
        array $datos
    ): void {

        // Comprobar que la mesa no esté en mantenimiento.
        if ($mesa->estado === 'mantenimiento') {
            throw ValidationException::withMessages([
                'id_mesa' => [
                    'La mesa seleccionada se encuentra en mantenimiento.',
                ],
            ]);
        }

        // Comprobar capacidad.
        if ($datos['cantidad_personas'] > $mesa->capacidad) {
            throw ValidationException::withMessages([
                'cantidad_personas' => [
                    'La cantidad de personas supera la capacidad de la mesa.',
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
                    'El horario de la reserva se encuentra fuera del horario de atención.',
                ],
            ]);
        }

        // Comprobar superposición de reservas.
        $conflicto = self::where('id_mesa', $mesa->id)
            ->whereDate('fecha', $datos['fecha'])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->where('hora_inicio', '<', $datos['hora_fin'])
            ->where('hora_fin', '>', $datos['hora_inicio'])
            ->exists();

        if ($conflicto) {
            throw ValidationException::withMessages([
                'hora_inicio' => [
                    'La mesa ya tiene una reserva en el horario seleccionado.',
                ],
            ]);
        }
    }

    // Registrar reserva

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            // Bloquear la mesa para evitar reservas simultáneas.
            $mesa = Mesa::whereKey($datos['id_mesa'])
                ->lockForUpdate()
                ->first();

            if (!$mesa) {
                throw ValidationException::withMessages([
                    'id_mesa' => [
                        'La mesa seleccionada no existe.',
                    ],
                ]);
            }

            // Comprobar que el cliente esté activo.
            $cliente = Cliente::whereKey($datos['id_cliente'])
                ->where('id_estado', '!=', 3)
                ->first();

            if (!$cliente) {
                throw ValidationException::withMessages([
                    'id_cliente' => [
                        'El cliente no existe o se encuentra eliminado.',
                    ],
                ]);
            }

            self::validarDisponibilidad($mesa, $datos);

            return self::create([
                'id_cliente' => $cliente->id,
                'id_mesa' => $mesa->id,
                'id_usuario' => $idUsuario,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'cantidad_personas' => $datos['cantidad_personas'],
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'pendiente',
            ])->load([
                'cliente',
                'mesa',
                'usuario',
            ]);
        });
    }

    // Transiciones de estado

    public static function cambiarEstado($id, string $nuevoEstado)
    {
        $estadosPermitidos = [
            'pendiente',
            'confirmada',
            'cancelada',
            'atendida',
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

        return DB::transaction(function () use ($id, $nuevoEstado) {

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
                    'atendida',
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

            $reserva->update([
                'estado' => $nuevoEstado,
            ]);

            return $reserva->fresh([
                'cliente',
                'mesa',
                'usuario',
            ]);
        });
    }
}
