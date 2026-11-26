<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    protected $casts = [
        'nombre' => 'string',
        'descripcion' => 'string'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_categoria');
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
                'max:50',
                Rule::unique('categorias', 'nombre')->ignore($id)
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:150'
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
        return self::withCount('productos')
            ->orderBy('nombre')
            ->get();
    }

    public static function buscarCategoriaXId($id)
    {
        return self::withCount('productos')->find($id);
    }

    public static function insertar($datos)
    {
        return self::create($datos);
    }

    public static function actualizar($id, $datos)
    {
        $categoria = self::find($id);

        if (!$categoria) {
            return null;
        }

        $categoria->update($datos);

        return $categoria;
    }

    public static function eliminar($id)
    {
        $categoria = self::find($id);

        if (!$categoria) {
            return false;
        }

        if ($categoria->productos()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => [
                    'La categoría tiene productos asociados y no puede eliminarse.',
                ],
            ]);
        }

        return $categoria->delete();
    }
}
