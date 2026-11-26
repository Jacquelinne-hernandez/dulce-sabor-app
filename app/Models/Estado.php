<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Estado extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Estados base del sistema
    |--------------------------------------------------------------------------
    | El login y la eliminación lógica dependen de estos registros, por lo
    | que no pueden eliminarse ni renombrarse. Los inserta EstadoSeeder.
    */

    public const ACTIVO = 1;
    public const BLOQUEADO = 2;
    public const ELIMINADO = 3;

    protected $table = 'estados';

    protected $fillable = [
        'estado',
        'descripcion'
    ];

    protected $casts = [
        'estado' => 'string',
        'descripcion' => 'string'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_estado');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'id_estado');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones($id = null): array
    {
        return [
            'estado' => [
                'required',
                'string',
                'max:50',
                Rule::unique('estados', 'estado')->ignore($id)
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:250'
            ]
        ];
    }

    public static function esBase($id): bool
    {
        return in_array(
            (int) $id,
            [self::ACTIVO, self::BLOQUEADO, self::ELIMINADO],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas y CRUD
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::orderBy('id')->get();
    }

    // Estados que se pueden asignar desde un formulario (sin ELIMINADO).
    public static function mostrarAsignables()
    {
        return self::where('id', '!=', self::ELIMINADO)
            ->orderBy('id')
            ->get();
    }

    public static function buscarEstadoXId($id)
    {
        return self::find($id);
    }

    public static function insertar($datos)
    {
        return self::create($datos);
    }

    public static function actualizar($id, $datos)
    {
        $estado = self::find($id);

        if (!$estado) {
            return null;
        }

        if (
            self::esBase($estado->id) &&
            strcasecmp($datos['estado'], $estado->estado) !== 0
        ) {
            throw ValidationException::withMessages([
                'estado' => [
                    'Los estados base del sistema no pueden renombrarse.',
                ],
            ]);
        }

        $estado->update($datos);

        return $estado;
    }

    public static function eliminar($id)
    {
        $estado = self::find($id);

        if (!$estado) {
            return false;
        }

        if (self::esBase($estado->id)) {
            throw ValidationException::withMessages([
                'estado' => [
                    'Los estados base del sistema no pueden eliminarse.',
                ],
            ]);
        }

        if (
            $estado->usuarios()->exists() ||
            $estado->clientes()->exists()
        ) {
            throw ValidationException::withMessages([
                'estado' => [
                    'El estado está en uso y no puede eliminarse.',
                ],
            ]);
        }

        return $estado->delete();
    }
}
