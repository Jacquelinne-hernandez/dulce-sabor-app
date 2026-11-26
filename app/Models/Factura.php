<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class Factura extends Model
{
    // Puntos de fidelidad que recibe el cliente por cada dólar facturado.
    public const PUNTOS_POR_DOLAR = 1;

    protected $table = 'facturas';

    protected $fillable = [
        'id_pedido',
        'id_cliente',
        'id_usuario',
        'numero_factura',
        'fecha_emision',
        'subtotal',
        'total',
        'efectivo_recibido',
        'cambio',
        'estado',
    ];

    protected $casts = [
        'id_pedido' => 'integer',
        'id_cliente' => 'integer',
        'id_usuario' => 'integer',
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'efectivo_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
        'estado' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleFactura::class, 'id_factura');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones(): array
    {
        return [
            'id_pedido' => [
                'required',
                'integer',
                'exists:pedidos,id',
            ],

            'efectivo_recibido' => [
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

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::with([
            'pedido',
            'cliente',
            'usuario',
            'detalles.producto',
        ])
            ->orderByDesc('fecha_emision')
            ->get();
    }

    public static function buscarFacturaXId($id)
    {
        return self::with([
            'pedido',
            'cliente',
            'usuario',
            'detalles.producto',
        ])->find($id);
    }

    public static function buscarFacturaXPedido($idPedido)
    {
        return self::with([
            'pedido',
            'cliente',
            'usuario',
            'detalles.producto',
        ])
            ->where('id_pedido', $idPedido)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Generación de facturas
    |--------------------------------------------------------------------------
    */

    public static function insertar(array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($datos, $idUsuario) {

            // Bloquear el pedido para evitar facturaciones simultáneas.
            $pedido = Pedido::where('id', $datos['id_pedido'])
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                throw ValidationException::withMessages([
                    'id_pedido' => 'El pedido seleccionado no existe.',
                ]);
            }

            // Solo se pueden facturar pedidos entregados.
            if ($pedido->estado !== 'entregado') {
                throw ValidationException::withMessages([
                    'id_pedido' => 'Solo se pueden facturar pedidos que hayan sido entregados.',
                ]);
            }

            // Comprobar que el pedido no tenga una factura previa.
            if (self::where('id_pedido', $pedido->id)->exists()) {
                throw ValidationException::withMessages([
                    'id_pedido' => 'Este pedido ya cuenta con una factura.',
                ]);
            }

            // Recuperar los detalles del pedido.
            $pedido->load('detalles.producto');

            if ($pedido->detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'id_pedido' => 'El pedido no contiene productos para facturar.',
                ]);
            }

            // Trabajar con centavos para evitar errores decimales.
            $totalCentavos = (int) round(
                (float) $pedido->total * 100
            );

            $efectivoCentavos = (int) round(
                (float) $datos['efectivo_recibido'] * 100
            );

            if ($efectivoCentavos < $totalCentavos) {
                throw ValidationException::withMessages([
                    'efectivo_recibido' => 'El efectivo recibido no cubre el total de la factura.',
                ]);
            }

            // Generar número de factura.
            $numeroFactura = 'FAC-' .
                now()->format('Ymd') . '-' .
                Str::upper(Str::random(8));

            // Crear factura.
            $factura = self::create([
                'id_pedido' => $pedido->id,
                'id_cliente' => $pedido->id_cliente,
                'id_usuario' => $idUsuario,
                'numero_factura' => $numeroFactura,
                'fecha_emision' => now(),
                'subtotal' => number_format(
                    $totalCentavos / 100,
                    2,
                    '.',
                    ''
                ),
                'total' => number_format(
                    $totalCentavos / 100,
                    2,
                    '.',
                    ''
                ),
                'efectivo_recibido' => number_format(
                    $efectivoCentavos / 100,
                    2,
                    '.',
                    ''
                ),
                'cambio' => number_format(
                    ($efectivoCentavos - $totalCentavos) / 100,
                    2,
                    '.',
                    ''
                ),
                'estado' => 'emitida',
            ]);

            // Crear los detalles de la factura.
            foreach ($pedido->detalles as $detalle) {

                $factura->detalles()->create([
                    'id_producto' => $detalle->id_producto,
                    'nombre_producto' => $detalle->producto->nombre,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                ]);
            }

            // Acumular los puntos de fidelidad del cliente.
            $puntos = intdiv($totalCentavos, 100) * self::PUNTOS_POR_DOLAR;

            if ($puntos > 0) {
                Cliente::whereKey($pedido->id_cliente)
                    ->increment('puntos', $puntos);
            }

            return $factura->load([
                'pedido',
                'cliente',
                'usuario',
                'detalles.producto',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Anulación de facturas
    |--------------------------------------------------------------------------
    */

    public static function anular($id)
    {
        return DB::transaction(function () use ($id) {

            $factura = self::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$factura) {
                return null;
            }

            if ($factura->estado === 'anulada') {
                throw ValidationException::withMessages([
                    'estado' => 'La factura ya se encuentra anulada.',
                ]);
            }

            $factura->update([
                'estado' => 'anulada',
            ]);

            // Retirar los puntos que otorgó la factura.
            $totalCentavos = (int) round((float) $factura->total * 100);
            $puntos = intdiv($totalCentavos, 100) * self::PUNTOS_POR_DOLAR;

            if ($puntos > 0) {
                $cliente = Cliente::where('id', $factura->id_cliente)
                    ->lockForUpdate()
                    ->first();

                if ($cliente) {
                    $cliente->update([
                        'puntos' => max(0, $cliente->puntos - $puntos),
                    ]);
                }
            }

            return $factura->load([
                'pedido',
                'cliente',
                'usuario',
                'detalles.producto',
            ]);
        });
    }
}
