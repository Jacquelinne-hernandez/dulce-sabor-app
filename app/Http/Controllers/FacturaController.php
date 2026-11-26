<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnularFacturaRequest;
use App\Http\Requests\InsertarFacturaRequest;
use App\Models\Bitacora;
use App\Models\Factura;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacturaController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::porFacturar();

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
        $factura = Factura::buscarFacturaXId($id);

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

    public function mostrarPorEstado(Request $request)
    {
        $datos = $request->validate([
            'estado' => ['required', 'string', 'in:emitida,pagada,anulada'],
        ], [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        return response()->json([
            'success' => true,
            'facturas' => Factura::mostrarPorEstado($datos['estado']),
        ]);
    }

    // Pedidos listos para facturar (orden entregada y sin factura).
    public function pedidosPorFacturar()
    {
        return response()->json([
            'success' => true,
            'pedidos' => Pedido::porFacturar(),
        ]);
    }

    // Vista previa: totales, IVA y puntos que se pueden canjear.
    public function cotizar(Request $request)
    {
        $datos = $request->validate(
            Factura::validaciones(),
            [
                'id_pedido.required' => 'Debe seleccionar un pedido.',
                'id_pedido.integer' => 'El identificador del pedido no es válido.',
                'id_pedido.exists' => 'El pedido seleccionado no existe.',
                'puntos_canjeados.integer' => 'Los puntos a canjear deben ser un número entero.',
                'puntos_canjeados.min' => 'Los puntos a canjear no pueden ser negativos.',
            ]
        );

        return response()->json([
            'success' => true,
            'cotizacion' => Factura::cotizar(
                (int) $datos['id_pedido'],
                (int) ($datos['puntos_canjeados'] ?? 0)
            ),
        ]);
    }

    public function insertarFactura(InsertarFacturaRequest $request)
    {
        $factura = Factura::insertar(
            $request->validated(),
            Auth::id()
        );

        $cliente = $factura->cliente
            ? $factura->cliente->codigo
            : 'consumidor final';

        Bitacora::registrar(
            Bitacora::CREADO,
            'FACTURAS',
            $factura->id,
            'Se emitió la factura ' . $factura->numero_factura
            . ' para el pedido #' . $factura->id_pedido
            . ' (' . $cliente . '), total $' . $factura->total
            . ($factura->puntos_canjeados > 0
                ? ', puntos canjeados: ' . $factura->puntos_canjeados
                : ''),
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'La factura se emitió correctamente.',
            'factura' => $factura,
        ], 201);
    }

    // Anulación de la venta completa (solo administrador, ver rutas).
    public function anularFactura(AnularFacturaRequest $request, $id)
    {
        $motivo = $request->validated()['motivo'];

        $factura = Factura::anular($id, Auth::id(), $motivo);

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
            'Se anuló la factura ' . $factura->numero_factura
            . ' y se canceló el pedido #' . $factura->id_pedido
            . '. Motivo: ' . $motivo,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'La factura se anuló correctamente, se revirtieron los pagos y puntos y se restituyeron las existencias.',
            'factura' => $factura,
        ]);
    }
}
