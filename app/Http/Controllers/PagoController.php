<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPagoRequest;
use App\Models\Bitacora;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;

class PagoController extends Controller
{
    public function mostrarPorFactura($id)
    {
        if (!Factura::whereKey($id)->exists()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La factura solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'pagos' => Pago::mostrarPorFactura($id),
        ]);
    }

    public function registrarPago(InsertarPagoRequest $request)
    {
        $datos = $request->validated();

        $pago = Pago::registrar(
            (int) $datos['id_factura'],
            $datos,
            Auth::id()
        );

        $factura = Factura::buscarFacturaXId($pago->id_factura);

        // En la bitácora nunca va el número de tarjeta: solo los últimos 4.
        $detalle = $pago->metodo === 'tarjeta'
            ? 'tarjeta ' . $pago->tarjeta_marca . ' terminada en ' . $pago->tarjeta_ultimos4
            : 'efectivo';

        Bitacora::registrar(
            Bitacora::CREADO,
            'PAGOS',
            $pago->id,
            'Pago de $' . $pago->monto . ' (' . $detalle . ') a la factura '
            . $factura->numero_factura
            . ($factura->estado === 'pagada' ? ', factura pagada' : ', saldo $' . $factura->saldo),
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => $factura->estado === 'pagada'
                ? 'Pago registrado. La factura quedó pagada.'
                : 'Abono registrado. Saldo pendiente: $' . $factura->saldo . '.',
            'pago' => $pago,
            'factura' => $factura,
        ], 201);
    }
}
