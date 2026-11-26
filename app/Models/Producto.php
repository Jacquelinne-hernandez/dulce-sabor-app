<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'id_categoria',
        'nombre',
        'precio',
        'costo',
        'stock',
        'stock_minimo',
        'descripcion',
        'imagen',
        'estado',
    ];

    protected $casts = [
        'id_categoria' => 'integer',
        'nombre' => 'string',
        'precio' => 'decimal:2',
        'costo' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'descripcion' => 'string',
        'imagen' => 'string',
        'estado' => 'string',
    ];

    // El costo es información del administrador: no se incluye en las
    // respuestas JSON (ni siquiera cuando el producto viaja dentro de un
    // pedido o una factura). Los endpoints del administrador lo muestran
    // con makeVisible('costo').
    protected $hidden = [
        'costo',
    ];

    // Relaciones

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function detallesCompras(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_producto');
    }

    public function detallesPedidos(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_producto');
    }

    public function detallesFacturas(): HasMany
    {
        return $this->hasMany(DetalleFactura::class, 'id_producto');
    }


    // Validaciones

    public static function validaciones($id = null): array
    {
        return [
            'id_categoria' => [
                'required',
                'integer',
                'exists:categorias,id',
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('productos', 'nombre')->ignore($id),
            ],
            'precio' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
            'costo' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'stock_minimo' => [
                'required',
                'integer',
                'min:0',
            ],
            'descripcion' => [
                'nullable',
                'string',
            ],
            'imagen' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    // Consultas

    public static function mostrarTodos()
    {
        return self::with('categoria')
            ->orderBy('nombre')
            ->get();
    }

    public static function mostrarDisponibles()
    {
        return self::with('categoria')
            ->where('estado', 'disponible')
            ->orderBy('nombre')
            ->get();
    }

    // Catálogo de solo lectura para el operador: existencias y precio,
    // sin costo, y sin productos inactivos.
    public static function mostrarCatalogo()
    {
        return self::select([
            'id',
            'id_categoria',
            'nombre',
            'precio',
            'stock',
            'stock_minimo',
            'descripcion',
            'imagen',
            'estado',
        ])
            ->with('categoria')
            ->where('estado', '!=', 'inactivo')
            ->orderBy('nombre')
            ->get();
    }

    public static function buscarProductoXId($id)
    {
        return self::with('categoria')->find($id);
    }

    // Operaciones CRUD

    public static function insertar(array $datos)
    {
        $datos['estado'] = $datos['stock'] > 0
            ? 'disponible'
            : 'agotado';

        return self::create($datos);
    }

    public static function actualizar($id, array $datos)
    {
        $producto = self::find($id);

        if (!$producto || $producto->estado === 'inactivo') {
            return null;
        }

        $datos['estado'] = $datos['stock'] > 0
            ? 'disponible'
            : 'agotado';

        $producto->update($datos);

        return $producto->fresh('categoria');
    }

    public static function eliminar($id): bool
    {
        $producto = self::find($id);

        if (!$producto || $producto->estado === 'inactivo') {
            return false;
        }

        $producto->update([
            'estado' => 'inactivo',
        ]);

        return true;
    }

    // Volver a activar un producto inactivo.
    public static function reactivar($id)
    {
        $producto = self::find($id);

        if (!$producto || $producto->estado !== 'inactivo') {
            return null;
        }

        $producto->update([
            'estado' => $producto->stock > 0
                ? 'disponible'
                : 'agotado',
        ]);

        return $producto->fresh('categoria');
    }

    // Productos cuyo stock llegó al mínimo configurado (o por debajo).
    public static function stockBajo()
    {
        return self::with('categoria')
            ->where('estado', '!=', 'inactivo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->get();
    }
}
