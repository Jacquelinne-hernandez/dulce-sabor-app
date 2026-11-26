<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsertarFacturaRequest;
use App\Models\Factura;
use App\Models\Pedido;
use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

class FacturaController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with('cliente')
            ->where('estado', 'entregado')
            ->whereDoesntHave('factura')
            ->orderByDesc('fecha')
            ->get();

        return view('factura.facturas', compact('pedidos'));
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'facturas' => Factura::mostrarTodos(),
        ]);
    }

    public function buscarFactura($id)
    {
        $datos = validator([
            'id' => $id,
        ], [
            'id' => [
                'required',
                'integer',
                'exists:facturas,id',
            ],
        ])->validate();

        $factura = Factura::buscarFacturaXId($datos['id']);

        if (!$factura) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La factura solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'factura' => $factura,
        ]);
    }

    public function insertarFactura(InsertarFacturaRequest $request)
    {
        $datos = $request->validated();

        $factura = Factura::insertar(
            $datos,
            Auth::id()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'FACTURAS',
            $factura->id,
            'Se emitió la factura ' . $factura->numero_factura .
            ' para el pedido #' . $factura->id_pedido,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'La factura se emitió correctamente.',
            'factura' => $factura,
        ], 201);
    }

    public function anularFactura($id)
    {
        $factura = Factura::anular($id);

        if (!$factura) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La factura solicitada no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'FACTURAS',
            $factura->id,
            'Se anuló la factura ' . $factura->numero_factura,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'La factura se anuló correctamente.',
            'factura' => $factura,
        ]);
    }
}
