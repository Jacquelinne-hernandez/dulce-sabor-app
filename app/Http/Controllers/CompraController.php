<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarAbonoCompraRequest;
use App\Http\Requests\InsertarCompraRequest;
use App\Models\Bitacora;
use App\Models\Compra;
use App\Models\PagoCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Auth;

class CompraController extends Controller
{
    // Es una pantalla del administrador: ve el costo actual de cada
    // producto para compararlo con el precio de compra.
    public function index()
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        $productos = Producto::where('estado', '!=', 'inactivo')
            ->orderBy('nombre')
            ->get()
            ->makeVisible('costo');

        return view('compra.compras', compact(
            'proveedores',
            'productos'
        ));
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'compras' => Compra::mostrarTodos(),
        ]);
    }

    public function buscarCompra($id)
    {
        $compra = Compra::buscarCompraXId($id);

        if (!$compra) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La compra solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'compra' => $compra,
        ]);
    }

    // Compras con saldo pendiente y resumen de la deuda con proveedores.
    public function mostrarDeudas()
    {
        return response()->json([
            'success' => true,
            'compras' => Compra::mostrarDeudas(),
            'resumen' => Compra::resumenDeudas(),
        ]);
    }

    public function insertarCompra(InsertarCompraRequest $request)
    {
        $compra = Compra::insertar(
            $request->validated(),
            Auth::id()
        );

        $condicion = $compra->condicion_pago === 'credito'
            ? 'a crédito (vence el ' . $compra->fecha_vencimiento->format('d/m/Y')
                . ', saldo $' . $compra->saldo . ')'
            : 'al contado';

        Bitacora::registrar(
            Bitacora::CREADO,
            'COMPRAS',
            $compra->id,
            'Se registró una compra por $' . $compra->total
            . ' del proveedor ' . $compra->proveedor->nombre
            . ' ' . $condicion,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Compra registrada correctamente. El inventario y el costo de los productos han sido actualizados.',
            'compra' => $compra,
        ], 201);
    }

    // Abono a una compra a crédito.
    public function registrarAbono(InsertarAbonoCompraRequest $request, $id)
    {
        $pago = PagoCompra::registrar(
            (int) $id,
            $request->validated(),
            Auth::id()
        );

        if (!$pago) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La compra solicitada no existe.',
            ], 404);
        }

        $compra = Compra::buscarCompraXId($id);

        Bitacora::registrar(
            Bitacora::CREADO,
            'COMPRAS',
            $compra->id,
            'Abono de $' . $pago->monto . ' a la compra #' . $compra->id
            . ' del proveedor ' . $compra->proveedor->nombre
            . ($compra->estado_pago === 'pagada'
                ? ', compra pagada'
                : ', saldo $' . $compra->saldo),
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => $compra->estado_pago === 'pagada'
                ? 'Abono registrado. La compra quedó pagada.'
                : 'Abono registrado. Saldo pendiente: $' . $compra->saldo . '.',
            'pago' => $pago,
            'compra' => $compra,
        ], 201);
    }
}
