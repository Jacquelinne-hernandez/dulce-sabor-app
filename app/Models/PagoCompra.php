<?php

namespace App\Models;

use App\Support\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Pago a un proveedor: el pago al contado, el abono inicial de una compra
// a crédito y los abonos posteriores.
class PagoCompra extends Model
{
    protected $table = 'pagos_compras';

    protected $fillable = [
        'id_compra',
        'id_usuario',
        'monto',
        'fecha_pago',
        'observaciones',
    ];

    protected $casts = [
        'id_compra' => 'integer',
        'id_usuario' => 'integer',
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'observaciones' => 'string',
    ];

    // Relaciones

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'monto' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'observaciones' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    // Consultas

    public static function mostrarPorCompra($idCompra)
    {
        return self::with('usuario:id,nombre,apellido')
            ->where('id_compra', $idCompra)
            ->orderBy('fecha_pago')
            ->orderBy('id')
            ->get();
    }

    // Registro de un abono

    public static function registrar(int $idCompra, array $datos, int $idUsuario)
    {
        return DB::transaction(function () use ($idCompra, $datos, $idUsuario) {

            $compra = Compra::whereKey($idCompra)
                ->lockForUpdate()
                ->first();

            if (!$compra) {
                return null;
            }

            if ($compra->estado_pago === 'pagada') {
                throw ValidationException::withMessages([
                    'monto' => ['Esta compra ya se encuentra pagada.'],
                ]);
            }

            $total = Dinero::centavos($compra->total);
            $pagado = Dinero::centavos($compra->monto_pagado);
            $saldo = $total - $pagado;

            $monto = Dinero::centavos($datos['monto']);

            if ($monto <= 0) {
                throw ValidationException::withMessages([
                    'monto' => ['El monto debe ser mayor que cero.'],
                ]);
            }

            if ($monto > $saldo) {
                throw ValidationException::withMessages([
                    'monto' => [
                        'El abono supera el saldo pendiente ($' . Dinero::formato($saldo) . ').',
                    ],
                ]);
            }

            $pago = self::create([
                'id_compra' => $compra->id,
                'id_usuario' => $idUsuario,
                'monto' => Dinero::formato($monto),
                'fecha_pago' => now(),
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $nuevoPagado = $pagado + $monto;

            $compra->update([
                'monto_pagado' => Dinero::formato($nuevoPagado),
                'estado_pago' => $nuevoPagado >= $total ? 'pagada' : 'parcial',
            ]);

            return $pago->load('usuario:id,nombre,apellido');
        });
    }
}
