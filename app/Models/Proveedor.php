<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
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

    // Productos que este proveedor ofrece, con el precio al que los vende
    // (pivot->precio_compra).
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(
            Producto::class,
            'proveedor_producto',
            'id_proveedor',
            'id_producto'
        )->withPivot('precio_compra')->withTimestamps();
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
                'max:100',
                Rule::unique('proveedores', 'nombre')->ignore($id)
            ],

            'nombre_contacto' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL][\pL\s.\'\-]*$/u'
            ],

            'telefono' => [
                'nullable',
                'string',
                'regex:/^\d{4}-\d{4}$/',
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
            ],

            /*
             * Productos que ofrece. Cada fila es un producto comprado ya
             * existente (id_producto) o uno nuevo que se crea en el momento
             * (nombre, id_categoria y precio_venta). Si "productos" no viene
             * en la petición, no se modifica la lista actual.
             */
            'productos' => [
                'nullable',
                'array',
                'max:100'
            ],

            'productos.*.id_producto' => [
                'nullable',
                'integer',
                'distinct',
                'required_without:productos.*.nombre',
                Rule::exists('productos', 'id')
                    ->where('origen', Producto::ORIGEN_COMPRA)
                    ->whereNot('estado', 'inactivo')
            ],

            'productos.*.nombre' => [
                'nullable',
                'string',
                'max:100',
                'distinct:ignore_case',
                'required_without:productos.*.id_producto',
                Rule::unique('productos', 'nombre')
            ],

            'productos.*.id_categoria' => [
                'nullable',
                'required_with:productos.*.nombre',
                'integer',
                'exists:categorias,id'
            ],

            'productos.*.precio_venta' => [
                'nullable',
                'required_with:productos.*.nombre',
                'numeric',
                'gt:0',
                'max:99999999.99'
            ],

            'productos.*.precio_compra' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99'
            ],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'nombre.unique' => 'Ya existe un proveedor con ese nombre.',

            'productos.array' => 'La lista de productos tiene un formato inválido.',
            'productos.max' => 'Un proveedor no puede ofrecer más de 100 productos.',

            'productos.*.id_producto.integer' => 'Uno de los productos seleccionados no es válido.',
            'productos.*.id_producto.distinct' => 'No puede repetir un producto en la lista del proveedor.',
            'productos.*.id_producto.required_without' => 'Seleccione un producto o escriba el nombre de uno nuevo.',
            'productos.*.id_producto.exists' => 'Uno de los productos no existe, está inactivo o no es de tipo comprado.',

            'productos.*.nombre.string' => 'El nombre del producto nuevo debe ser texto.',
            'productos.*.nombre.max' => 'El nombre del producto nuevo no puede superar los 100 caracteres.',
            'productos.*.nombre.distinct' => 'No puede repetir el nombre de un producto nuevo.',
            'productos.*.nombre.required_without' => 'Seleccione un producto o escriba el nombre de uno nuevo.',
            'productos.*.nombre.unique' => 'Ya existe un producto con ese nombre: selecciónelo de la lista.',

            'productos.*.id_categoria.required_with' => 'Seleccione la categoría del producto nuevo.',
            'productos.*.id_categoria.exists' => 'La categoría seleccionada no existe.',

            'productos.*.precio_venta.required_with' => 'Indique el precio de venta del producto nuevo.',
            'productos.*.precio_venta.numeric' => 'El precio de venta debe ser un valor numérico.',
            'productos.*.precio_venta.gt' => 'El precio de venta debe ser mayor que cero.',
            'productos.*.precio_venta.max' => 'El precio de venta supera el límite permitido.',

            'productos.*.precio_compra.required' => 'Indique el precio que ofrece el proveedor.',
            'productos.*.precio_compra.numeric' => 'El precio del proveedor debe ser un valor numérico.',
            'productos.*.precio_compra.gt' => 'El precio del proveedor debe ser mayor que cero.',
            'productos.*.precio_compra.max' => 'El precio del proveedor supera el límite permitido.',
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

    // Proveedor con los productos activos que ofrece.
    public static function buscarProveedorXId($id)
    {
        return self::with([
            'productos' => fn ($consulta) => $consulta
                ->where('productos.estado', '!=', 'inactivo')
                ->orderBy('productos.nombre'),
        ])->find($id);
    }

    // Proveedores con sus productos activos (para las compras).
    public static function mostrarConProductos()
    {
        return self::with([
            'productos' => fn ($consulta) => $consulta
                ->where('productos.estado', '!=', 'inactivo')
                ->orderBy('productos.nombre'),
        ])
            ->orderBy('nombre')
            ->get();
    }

    // Indica si el proveedor ofrece TODOS los productos indicados.
    public static function ofreceProductos(int $idProveedor, $idsProductos): bool
    {
        $ids = collect($idsProductos)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return true;
        }

        $proveedor = self::find($idProveedor);

        if (!$proveedor) {
            return false;
        }

        return $proveedor->productos()
            ->whereIn('productos.id', $ids)
            ->count() === $ids->count();
    }

    public static function insertar($datos)
    {
        return DB::transaction(function () use ($datos) {

            $filas = $datos['productos'] ?? null;
            unset($datos['productos']);

            $proveedor = self::create($datos);

            if ($filas !== null) {
                self::sincronizarProductos($proveedor, $filas);
            }

            return $proveedor->load('productos');
        });
    }

    public static function actualizar($id, $datos)
    {
        return DB::transaction(function () use ($id, $datos) {

            $proveedor = self::find($id);

            if (!$proveedor) {
                return null;
            }

            $filas = $datos['productos'] ?? null;
            unset($datos['productos']);

            $proveedor->update($datos);

            if ($filas !== null) {
                self::sincronizarProductos($proveedor, $filas);
            }

            return $proveedor->load('productos');
        });
    }

    // Deja en el proveedor exactamente los productos de la lista. Las filas
    // sin id_producto crean un producto nuevo de tipo "compra" (sin stock:
    // las existencias llegan con la primera compra).
    private static function sincronizarProductos(self $proveedor, array $filas): void
    {
        $sincronizar = [];

        foreach ($filas as $fila) {

            $idProducto = $fila['id_producto'] ?? null;

            if (!$idProducto) {
                $producto = Producto::insertar([
                    'id_categoria' => $fila['id_categoria'],
                    'nombre' => $fila['nombre'],
                    'precio' => $fila['precio_venta'],
                    'costo' => $fila['precio_compra'],
                    'stock' => 0,
                    'stock_minimo' => 0,
                    'descripcion' => null,
                    'origen' => Producto::ORIGEN_COMPRA,
                ]);

                $idProducto = $producto->id;
            }

            $sincronizar[(int) $idProducto] = [
                'precio_compra' => $fila['precio_compra'],
            ];
        }

        $proveedor->productos()->sync($sincronizar);
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
