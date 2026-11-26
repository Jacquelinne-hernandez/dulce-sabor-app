<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'id_cliente',
        'id_mesa',
        'id_usuario',
        'fecha',
        'total',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'id_cliente' => 'integer',
        'id_mesa' => 'integer',
        'id_usuario' => 'integer',
        'fecha' => 'datetime',
        'total' => 'decimal:2',
        'estado' => 'string',
        'observaciones' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'id_mesa');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_pedido');
    }
    public function factura(): HasOne
    {
        return $this->hasOne(Factura::class, 'id_pedido');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones(): array
    {
        return [
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],

            'id_mesa' => [
                'nullable',
                'integer',
                'exists:mesas,id',
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

            'observaciones' => [
                'nullable',
                'string',
            ],
        ];
    }

    public static function reglasEstado(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in([
                    'pendiente',
                    'en_preparacion',
                    'listo',
                    'entregado',
                    'cancelado',
                ]),
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

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::with([
            'cliente',
            'mesa',
            'usuario',
            'detalles.producto',
        ])
            ->orderByDesc('fecha')
            ->get();
    }

    public static function buscarPedidoXId($id)
    {
        return self::with([
            'cliente',
            'mesa',
            'usuario',
            'detalles.producto',
        ])->find($id);
    }

    public static function mostrarPorFecha($fecha)
    {
        return self::with([
            'cliente',
            'mesa',
            'usuario',
            'detalles.producto',
        ])
            ->whereDate('fecha', $fecha)
            ->orderByDesc('fecha')
            ->get();
    }

    public static function mostrarPorEstado($estado)
    {
        return self::with([
            'cliente',
            'mesa',
            'usuario',
            'detalles.producto',
        ])
            ->where('estado', $estado)
            ->orderByDesc('fecha')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Registro de pedidos
    |--------------------------------------------------------------------------
    */

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            // Verificar que el cliente esté activo.
            $cliente = Cliente::where('id', $datos['id_cliente'])
                ->lockForUpdate()
                ->first();

            if (!$cliente || $cliente->id_estado == Estado::ELIMINADO) {
                throw ValidationException::withMessages([
                    'id_cliente' => 'El cliente seleccionado no está disponible.',
                ]);
            }

            // Verificar la mesa, si el pedido es para consumir en el local.
            if (!empty($datos['id_mesa'])) {
                $mesa = Mesa::where('id', $datos['id_mesa'])
                    ->lockForUpdate()
                    ->first();

                if (!$mesa || $mesa->estado === 'mantenimiento') {
                    throw ValidationException::withMessages([
                        'id_mesa' => 'La mesa seleccionada no está disponible.',
                    ]);
                }
            }

            // Obtener los identificadores y bloquear los productos
            // siempre en el mismo orden para reducir interbloqueos.
            $idsProductos = collect($datos['detalles'])
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
            $detallesPreparados = [];

            foreach ($datos['detalles'] as $detalle) {

                $producto = $productos->get($detalle['id_producto']);
                $cantidad = (int) $detalle['cantidad'];

                if (!$producto || $producto->estado !== 'disponible') {
                    throw ValidationException::withMessages([
                        'detalles' => 'Uno de los productos seleccionados no está disponible.',
                    ]);
                }

                if ($producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'detalles' => "Existencias insuficientes para el producto {$producto->nombre}.",
                    ]);
                }

                // Trabajar en centavos para evitar errores de precisión decimal.
                $precioCentavos = (int) round(
                    (float) $producto->precio * 100
                );

                $subtotalCentavos = $precioCentavos * $cantidad;

                $totalCentavos += $subtotalCentavos;

                $detallesPreparados[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_centavos' => $precioCentavos,
                    'subtotal_centavos' => $subtotalCentavos,
                ];
            }

            // DECIMAL(10,2): máximo 99,999,999.99.
            if ($totalCentavos > 9999999999) {
                throw ValidationException::withMessages([
                    'detalles' => 'El total del pedido supera el límite permitido.',
                ]);
            }

            // Crear el pedido.
            $pedido = self::create([
                'id_cliente' => $datos['id_cliente'],
                'id_mesa' => $datos['id_mesa'] ?? null,
                'id_usuario' => $idUsuario,
                'fecha' => now(),
                'total' => number_format($totalCentavos / 100, 2, '.', ''),
                'estado' => 'pendiente',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            // Crear detalles y descontar existencias.
            foreach ($detallesPreparados as $detalle) {

                $producto = $detalle['producto'];

                $pedido->detalles()->create([
                    'id_producto' => $producto->id,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => number_format(
                        $detalle['precio_centavos'] / 100,
                        2,
                        '.',
                        ''
                    ),
                    'subtotal' => number_format(
                        $detalle['subtotal_centavos'] / 100,
                        2,
                        '.',
                        ''
                    ),
                ]);

                $producto->stock -= $detalle['cantidad'];

                if ($producto->stock === 0) {
                    $producto->estado = 'agotado';
                }

                $producto->save();
            }

            // Si el pedido es para consumir en el local, la mesa pasa a ocupada.
            if (isset($mesa)) {
                $mesa->update([
                    'estado' => 'ocupada',
                ]);
            }

            return $pedido->load([
                'cliente',
                'mesa',
                'usuario',
                'detalles.producto',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Actualización de estado y restitución de existencias
    |--------------------------------------------------------------------------
    */

    public static function cambiarEstado($id, string $nuevoEstado)
    {
        return DB::transaction(function () use ($id, $nuevoEstado) {

            $pedido = self::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                return null;
            }

            $transiciones = [
                'pendiente' => [
                    'en_preparacion',
                    'cancelado',
                ],

                'en_preparacion' => [
                    'listo',
                    'cancelado',
                ],

                'listo' => [
                    'entregado',
                    'cancelado',
                ],

                'entregado' => [],
                'cancelado' => [],
            ];

            if (!in_array(
                $nuevoEstado,
                $transiciones[$pedido->estado] ?? [],
                true
            )) {
                throw ValidationException::withMessages([
                    'estado' => 'La transición de estado solicitada no está permitida.',
                ]);
            }

            // Si se cancela, restituir las existencias.
            if ($nuevoEstado === 'cancelado') {

                $detalles = $pedido->detalles()
                    ->orderBy('id_producto')
                    ->get();

                $idsProductos = $detalles
                    ->pluck('id_producto')
                    ->unique()
                    ->sort()
                    ->values();

                $productos = Producto::whereIn('id', $idsProductos)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($detalles as $detalle) {

                    $producto = $productos->get($detalle->id_producto);

                    if (!$producto) {
                        throw ValidationException::withMessages([
                            'estado' => 'No se pudo restituir el inventario de uno de los productos.',
                        ]);
                    }

                    $nuevoStock = $producto->stock + $detalle->cantidad;

                    // Límite de UNSIGNED INT en MySQL.
                    if ($nuevoStock > 4294967295) {
                        throw ValidationException::withMessages([
                            'estado' => 'La restitución supera el límite de existencias permitido.',
                        ]);
                    }

                    $producto->stock = $nuevoStock;

                    if ($producto->estado !== 'inactivo') {
                        $producto->estado = 'disponible';
                    }

                    $producto->save();
                }
            }

            $pedido->estado = $nuevoEstado;
            $pedido->save();

            // Al entregar o cancelar, se libera la mesa si ya no tiene
            // otros pedidos en curso.
            if (in_array($nuevoEstado, ['entregado', 'cancelado'], true)) {
                self::liberarMesa($pedido);
            }

            return $pedido->load([
                'cliente',
                'mesa',
                'usuario',
                'detalles.producto',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Liberación de mesas
    |--------------------------------------------------------------------------
    */

    private static function liberarMesa(Pedido $pedido): void
    {
        if (!$pedido->id_mesa) {
            return;
        }

        $hayPedidosEnCurso = self::where('id_mesa', $pedido->id_mesa)
            ->where('id', '!=', $pedido->id)
            ->whereIn('estado', ['pendiente', 'en_preparacion', 'listo'])
            ->exists();

        if ($hayPedidosEnCurso) {
            return;
        }

        $mesa = Mesa::where('id', $pedido->id_mesa)
            ->lockForUpdate()
            ->first();

        if ($mesa && $mesa->estado === 'ocupada') {
            $mesa->update([
                'estado' => 'disponible',
            ]);
        }
    }
}
