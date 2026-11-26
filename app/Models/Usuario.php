<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usuario extends Authenticatable
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'id_rol',
        'id_estado',
        'nombre',
        'apellido',
        'email',
        'password',
        'telefono'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'id_rol' => 'integer',
        'id_estado' => 'integer',
        'password' => 'hashed'
    ];

    /**
     * La tabla usuarios no tiene la columna remember_token, por lo que
     * se desactiva el "recordarme" de Laravel.
     */
    public function getRememberTokenName()
    {
        return '';
    }

    //Relación
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'id_usuario');
    }


    // Relación: un usuario pertenece a un rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    // Relación: un usuario pertenece a un estado
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_usuario');
    }
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_usuario');
    }
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_usuario');
    }


    // Obtener las reglas de validación
    public static function validaciones($id = null): array
    {
        return [
            'id_rol' => [
                'required',
                'integer',
                'exists:roles,id'
            ],

            'id_estado' => [
                'required',
                'integer',
                'exists:estados,id'
            ],

            'nombre' => [
                'required',
                'string',
                'max:50'
            ],

            'apellido' => [
                'required',
                'string',
                'max:50'
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('usuarios', 'email')->ignore($id)
            ],

            'password' => $id === null
                ? [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed'
                ]
                : [
                    'nullable',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed'
                ],

            'telefono' => [
                'nullable',
                'string',
                'max:9',
                Rule::unique('usuarios', 'telefono')->ignore($id)
            ]
        ];
    }


    // Validar los datos proporcionados
    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    // Reglas para el inicio de sesión
    public static function loginRules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:100'
            ],

            'password' => [
                'required',
                'string'
            ]
        ];
    }

    // Obtener todos los usuarios (excluye los eliminados)
    public static function mostrarTodos()
    {
        return self::with(['rol', 'estado'])
            ->where('id_estado', '!=', Estado::ELIMINADO)
            ->orderBy('nombre')
            ->get();
    }

    // Buscar un usuario por su ID
    public static function buscarUsuarioXId($id)
    {
        return self::with(['rol', 'estado'])
            ->where('id_estado', '!=', Estado::ELIMINADO)
            ->find($id);
    }

    // Insertar un nuevo usuario
    public static function insertar($datos)
    {
        return self::create($datos);
    }

    // Actualizar un usuario existente
    public static function actualizar($id, $datos)
    {
        $usuario = self::where('id_estado', '!=', Estado::ELIMINADO)
            ->find($id);

        if (!$usuario) {
            return null;
        }

        // Si no se envía una nueva contraseña, se conserva la actual.
        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return $usuario;
    }

    // Eliminación lógica: el registro se conserva para auditoría.
    public static function eliminar($id)
    {
        $usuario = self::where('id_estado', '!=', Estado::ELIMINADO)
            ->find($id);

        if (!$usuario) {
            return false;
        }

        $usuario->update([
            'id_estado' => Estado::ELIMINADO
        ]);

        return true;
    }

    // Cantidad de usuarios por estado (para el dashboard)
    public static function estadosDeUsuario()
    {
        return Estado::withCount('usuarios')
            ->orderBy('id')
            ->get()
            ->map(fn ($estado) => [
                'estado' => $estado->estado,
                'total' => $estado->usuarios_count,
            ]);
    }
}
