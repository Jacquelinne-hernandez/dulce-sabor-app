<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'nombre_contacto',
        'telefono',
        'email',
        'direccion',
        'descripcion'
    ];

    protected $casts = [
        'nombre' => 'string',
        'nombre_contacto' => 'string',
        'telefono' => 'string',
        'email' => 'string',
        'direccion' => 'string',
        'descripcion' => 'string'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'id_proveedor');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones($id = null): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100'
            ],

            'nombre_contacto' => [
                'nullable',
                'string',
                'max:100'
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:9',
                Rule::unique('proveedores', 'telefono')->ignore($id)
            ],

            'email' => [
                'nullable',
                'string',
                'email',
                'max:100',
                Rule::unique('proveedores', 'email')->ignore($id)
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:200'
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:250'
            ]
        ];
    }

    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas y CRUD
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::orderBy('nombre')->get();
    }

    public static function buscarProveedorXId($id)
    {
        return self::find($id);
    }

    public static function insertar($datos)
    {
        return self::create($datos);
    }

    public static function actualizar($id, $datos)
    {
        $proveedor = self::find($id);

        if (!$proveedor) {
            return null;
        }

        $proveedor->update($datos);

        return $proveedor;
    }

    public static function eliminar($id)
    {
        $proveedor = self::find($id);

        if (!$proveedor) {
            return false;
        }

        if ($proveedor->compras()->exists()) {
            throw ValidationException::withMessages([
                'proveedor' => [
                    'El proveedor tiene compras registradas y no puede eliminarse.',
                ],
            ]);
        }

        return $proveedor->delete();
    }
}
