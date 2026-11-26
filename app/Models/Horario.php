<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Horario extends Model
{
    use HasFactory;

    // Nombres de los días según el formato ISO (1 = lunes ... 7 = domingo).
    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    protected $table = 'horarios';

    protected $fillable = [
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
        'activo'
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'hora_apertura' => 'string',
        'hora_cierre' => 'string',
        'activo' => 'boolean'
    ];

    // Incluye el nombre del día al convertir el modelo a JSON.
    protected $appends = [
        'nombre_dia'
    ];

    public function getNombreDiaAttribute(): string
    {
        return self::DIAS[$this->dia_semana] ?? 'Desconocido';
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    | Los días de la semana se crean con HorarioSeeder; desde el sistema
    | únicamente se modifica la hora de apertura, cierre y si atiende o no.
    */

    public static function validaciones(): array
    {
        return [
            'activo' => [
                'required',
                'boolean'
            ],

            'hora_apertura' => [
                'nullable',
                'required_if:activo,true,1',
                'date_format:H:i'
            ],

            'hora_cierre' => [
                'nullable',
                'required_if:activo,true,1',
                'date_format:H:i',
                'after:hora_apertura'
            ]
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
    | Consultas y CRUD
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::orderBy('dia_semana')->get();
    }

    public static function buscarHorarioXId($id)
    {
        return self::find($id);
    }

    public static function buscarHorarioXDia($dia)
    {
        return self::where('dia_semana', $dia)->first();
    }

    public static function actualizar($id, $datos)
    {
        $horario = self::find($id);

        if (!$horario) {
            return null;
        }

        $horario->update($datos);

        return $horario->fresh();
    }
}
