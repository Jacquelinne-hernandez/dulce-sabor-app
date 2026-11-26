<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'id_proveedor',
        'id_usuario',
        'fecha_compra',
        'total',
        'estado',
    ];

    protected $casts = [
        'id_proveedor' => 'integer',
        'id_usuario' => 'integer',
        'fecha_compra' => 'datetime',
        'total' => 'decimal:2',
        'estado' => 'string',
    ];

    // Relaciones

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'id_proveedor' => [
                'required',
                'integer',
                'exists:proveedores,id',
            ],

            'detalles' => [
                'required',
                'array',
                'min:1',
            ],

            'detalles.*.id_producto' => [
                'required',
                'integer',
                'distinct',
                'exists:productos,id',
            ],

            'detalles.*.cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'detalles.*.precio_unitario' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    // Consultas

    public static function mostrarTodos()
    {
        return self::with([
            'proveedor',
            'usuario',
            'detalles.producto',
        ])
            ->orderByDesc('fecha_compra')
            ->get();
    }

    public static function buscarCompraXId($id)
    {
        return self::with([
            'proveedor',
            'usuario',
            'detalles.producto',
        ])->find($id);
    }

    // Registrar compra e incrementar inventario

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            $detalles = $datos['detalles'];

            // Bloquear los productos para evitar modificaciones
            // simultáneas de sus existencias.
            $idsProductos = collect($detalles)
                ->pluck('id_producto')
                ->unique()
                ->sort()
                ->values();

            $productos = Producto::whereIn('id', $idsProductos)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalCentavos = 0;

            // Comprobar que todos los productos puedan comprarse.
            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle['id_producto']);

                if (!$producto || $producto->estado === 'inactivo') {
                    throw ValidationException::withMessages([
                        'detalles' => [
                            'Uno o más productos no existen o se encuentran inactivos.',
                        ],
                    ]);
                }

                $precioCentavos = (int) round(
                    (float) $detalle['precio_unitario'] * 100
                );

                $subtotalCentavos = $precioCentavos * (int) $detalle['cantidad'];

                $totalCentavos += $subtotalCentavos;

                if (
                    (int) $producto->stock + (int) $detalle['cantidad']
                    > 1000
                ) {
                    throw ValidationException::withMessages([
                        'detalles' => [
                            'La cantidad supera el límite de existencias permitido.',
                        ],
                    ]);
                }
            }

            // Crear la cabecera de la compra.
            $compra = self::create([
                'id_proveedor' => $datos['id_proveedor'],
                'id_usuario' => $idUsuario,
                'fecha_compra' => now(),
                'total' => number_format(
                    $totalCentavos / 100,
                    2,
                    '.',
                    ''
                ),
                'estado' => 'completada',
            ]);

            // Registrar detalles e incrementar existencias.
            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle['id_producto']);

                $precioCentavos = (int) round(
                    (float) $detalle['precio_unitario'] * 100
                );

                $subtotalCentavos = $precioCentavos * (int) $detalle['cantidad'];

                $compra->detalles()->create([
                    'id_producto' => $producto->id,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => number_format(
                        $precioCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'subtotal' => number_format(
                        $subtotalCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                ]);

                // Incrementar stock sin modificar productos inactivos.
                $producto->increment('stock', $detalle['cantidad']);

                if ($producto->estado !== 'inactivo') {
                    $producto->update([
                        'estado' => 'disponible',
                    ]);
                }
            }

            return $compra->load([
                'proveedor',
                'usuario',
                'detalles.producto',
            ]);
        });
    }
}
