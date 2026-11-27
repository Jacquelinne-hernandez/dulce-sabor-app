<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Producto extends Model
{
    // Origen del producto: elaborado en la pastelería o comprado a un proveedor.
    public const ORIGEN_PRODUCCION = 'produccion';
    public const ORIGEN_COMPRA = 'compra';
    public const ORIGENES = [self::ORIGEN_PRODUCCION, self::ORIGEN_COMPRA];

    // Las imágenes se guardan en storage/app/files/productos (disco "files",
    // definido en config/filesystems.php), fuera de la carpeta pública.
    public const DISCO = 'files';
    public const CARPETA_IMAGENES = 'productos';
    public const IMAGEN_MAX_KB = 2048;

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
        'origen',
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
        'origen' => 'string',
    ];

    // El costo es información del administrador: no se incluye en las
    // respuestas JSON (ni siquiera cuando el producto viaja dentro de un
    // pedido o una factura). Los endpoints del administrador lo muestran
    // con makeVisible('costo').
    protected $hidden = [
        'costo',
    ];

    // Dirección con la que el navegador pide la imagen (ver imagen_url).
    protected $appends = [
        'imagen_url',
    ];

    // Relaciones

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    // Proveedores que ofrecen este producto (solo los de origen "compra").
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(
            Proveedor::class,
            'proveedor_producto',
            'id_producto',
            'id_proveedor'
        )->withPivot('precio_compra')->withTimestamps();
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

    // Imagen

    // Dirección de la imagen. Las fotos subidas se sirven por una ruta
    // protegida (productos.imagen), no desde una carpeta pública. El
    // parámetro "v" cambia con cada foto nueva para que el navegador no
    // muestre la anterior.
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(function () {
            $imagen = $this->attributes['imagen'] ?? null;
            $id = $this->attributes['id'] ?? null;

            if (!$imagen) {
                return null;
            }

            if (preg_match('#^https?://#i', $imagen)) {
                return $imagen;
            }

            if (!$id || !str_starts_with($imagen, self::CARPETA_IMAGENES . '/')) {
                return null;
            }

            return route('productos.imagen', [
                'id' => $id,
                'v' => substr(md5($imagen), 0, 8),
            ], false);
        });
    }

    // Guarda la foto del producto con un nombre que lo identifica:
    // PRD-<id con 5 cifras>-<código aleatorio>.<extensión>
    // y elimina la foto anterior para no acumular archivos huérfanos.
    public static function guardarImagen(self $producto, UploadedFile $archivo): self
    {
        $extension = match ($archivo->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $nombre = sprintf(
            'PRD-%05d-%s.%s',
            $producto->id,
            Str::lower(Str::random(10)),
            $extension
        );

        $ruta = Storage::disk(self::DISCO)
            ->putFileAs(self::CARPETA_IMAGENES, $archivo, $nombre);

        if (!$ruta) {
            throw ValidationException::withMessages([
                'imagen' => ['No fue posible guardar la imagen en el servidor.'],
            ]);
        }

        $anterior = $producto->imagen;

        $producto->update(['imagen' => $ruta]);

        self::borrarArchivo($anterior);

        return $producto;
    }

    public static function quitarImagen(self $producto): self
    {
        $anterior = $producto->imagen;

        $producto->update(['imagen' => null]);

        self::borrarArchivo($anterior);

        return $producto;
    }

    // Solo se borran archivos de nuestra carpeta; un fallo nunca interrumpe
    // la operación (el archivo huérfano es inofensivo).
    private static function borrarArchivo(?string $ruta): void
    {
        if (!$ruta || !str_starts_with($ruta, self::CARPETA_IMAGENES . '/')) {
            return;
        }

        try {
            Storage::disk(self::DISCO)->delete($ruta);
        } catch (\Throwable $e) {
            report($e);
        }
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
            'origen' => [
                'required',
                'string',
                Rule::in(self::ORIGENES),
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
                'max:' . Compra::STOCK_MAXIMO,
            ],
            'stock_minimo' => [
                'required',
                'integer',
                'min:0',
                'max:' . Compra::STOCK_MAXIMO,
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'origen.required' => 'Indique si el producto es de producción o comprado.',
            'origen.in' => 'El origen debe ser producción o compra.',
            'stock.max' => 'El stock no puede superar ' . Compra::STOCK_MAXIMO . ' unidades.',
            'stock_minimo.max' => 'El stock mínimo no puede superar ' . Compra::STOCK_MAXIMO . ' unidades.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
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

    // Productos que se compran a proveedores (para asignarlos a uno).
    public static function mostrarComprados()
    {
        return self::where('origen', self::ORIGEN_COMPRA)
            ->where('estado', '!=', 'inactivo')
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
            'origen',
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

        $datos['origen'] = $datos['origen'] ?? self::ORIGEN_PRODUCCION;

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

        // Un producto que ahora se elabora en la pastelería deja de
        // figurar entre los productos de los proveedores.
        if ($producto->origen === self::ORIGEN_PRODUCCION) {
            $producto->proveedores()->detach();
        }

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
