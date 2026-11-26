<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Rol extends Model
{
    // SECCIÓN 1: REGLAS Y CONFIGURACIONES DEL MODELO

    /**
     * Roles base del sistema. Los inserta RolSeeder y los permisos de las
     * rutas dependen de sus nombres, por lo que no pueden renombrarse
     * ni eliminarse.
     */
    public const BASE = ['Administrador', 'Operador'];

    /**
     * Las tablas están en español, por lo que se indica explícitamente
     * el nombre de la tabla.
     */
    protected $table = 'roles';

    /**
     * Campos que se llenan de forma masiva (sin ids ni timestamps).
     */
    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    protected $casts = [
        'nombre' => 'string',
        'descripcion' => 'string'
    ];

    // SECCIÓN 2: RELACIONES Y VALIDACIONES

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_rol');
    }

    public static function esBase(string $nombre): bool
    {
        return in_array($nombre, self::BASE, true);
    }

    // SECCIÓN 3: CRUD

    public static function mostrarTodos()
    {
        return self::orderBy('id')->get();
    }

    // Buscar un rol por su ID
    public static function buscarRolXId($id)
    {
        return self::find($id);
    }

    // Insertar un nuevo rol
    public static function insertar($datos)
    {
        return self::create($datos);
    }

    // Actualizar un rol existente
    public static function actualizar($id, $datos)
    {
        $rol = self::find($id);

        if (!$rol) {
            return null;
        }

        if (
            self::esBase($rol->nombre) &&
            ($datos['nombre'] ?? $rol->nombre) !== $rol->nombre
        ) {
            throw ValidationException::withMessages([
                'nombre' => [
                    'Los roles base del sistema no pueden renombrarse.',
                ],
            ]);
        }

        $rol->update($datos);

        return $rol;
    }

    // Eliminar un rol
    public static function eliminar($id)
    {
        $rol = self::find($id);

        if (!$rol) {
            return false;
        }

        if (self::esBase($rol->nombre)) {
            throw ValidationException::withMessages([
                'rol' => [
                    'Los roles base del sistema no pueden eliminarse.',
                ],
            ]);
        }

        if ($rol->usuarios()->exists()) {
            throw ValidationException::withMessages([
                'rol' => [
                    'El rol tiene usuarios asignados y no puede eliminarse.',
                ],
            ]);
        }

        return $rol->delete();
    }
}
