<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
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
        'telefono',
        'debe_cambiar_password'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'id_rol' => 'integer',
        'id_estado' => 'integer',
        'password' => 'hashed',
        'debe_cambiar_password' => 'boolean'
    ];

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
                'max:50',
                'regex:/^[\pL][\pL\s.\'\-]*$/u'
            ],

            'apellido' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL][\pL\s.\'\-]*$/u'
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('usuarios', 'email')->ignore($id)
            ],

            'password' => static::reglasPassword($id === null),

            'telefono' => [
                'nullable',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('usuarios', 'telefono')->ignore($id)
            ]
        ];
    }


    // Política de contraseñas: mínimo 8 caracteres, con mayúscula,
    // minúscula, número y un carácter especial, sin espacios.
    public static function reglasPassword(bool $obligatoria = true): array
    {
        return [
            $obligatoria ? 'required' : 'nullable',
            'string',
            'min:8',
            'max:72',
            'confirmed',
            function (string $atributo, $valor, \Closure $fallar) {
                if (!is_string($valor) || $valor === '') {
                    return;
                }

                if (preg_match('/\s/u', $valor)) {
                    $fallar('La contraseña no debe contener espacios.');
                } elseif (!preg_match('/\p{Ll}/u', $valor)) {
                    $fallar('La contraseña debe incluir al menos una letra minúscula.');
                } elseif (!preg_match('/\p{Lu}/u', $valor)) {
                    $fallar('La contraseña debe incluir al menos una letra mayúscula.');
                } elseif (!preg_match('/\d/', $valor)) {
                    $fallar('La contraseña debe incluir al menos un número.');
                } elseif (!preg_match('/[^\p{L}\p{N}\s]/u', $valor)) {
                    $fallar('La contraseña debe incluir al menos un carácter especial (por ejemplo ! @ # $ % & *).');
                }
            },
        ];
    }

    public static function mensajesPassword(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no debe superar los 72 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
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
        // El administrador define una contraseña inicial: el usuario debe
        // cambiarla antes de poder usar el sistema.
        $datos['debe_cambiar_password'] = true;

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
        } else {
            // Si el administrador restablece la contraseña de otra persona,
            // esa persona debe cambiarla al ingresar; si cambia la propia,
            // no hace falta.
            $datos['debe_cambiar_password'] = (int) $id !== (int) Auth::id();
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
