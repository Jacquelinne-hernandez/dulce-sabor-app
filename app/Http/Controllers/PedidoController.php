<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEstadoPedidoRequest;
use App\Http\Requests\InsertarPedidoRequest;
use App\Models\Bitacora;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PedidoController extends Controller
{
    // El cliente se busca por código o nombre (ClienteController) y las
    // reservas del día se piden con reservasPorAtender, así que la vista
    // solo recibe las mesas y los productos que se pueden vender.
    public function index()
    {
        $mesas = Mesa::where('estado', '!=', 'mantenimiento')
            ->orderBy('numero')
            ->get();

        return view('pedido.pedidos', compact('mesas'));
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'pedidos' => Pedido::mostrarTodos(),
        ]);
    }

    public function buscarPedido($id)
    {
        $pedido = Pedido::buscarPedidoXId($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El pedido solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'pedido' => $pedido,
        ]);
    }

    public function mostrarPorFecha(Request $request)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
        ], [
            'fecha.required' => 'Debe indicar una fecha.',
            'fecha.date' => 'La fecha proporcionada no es válida.',
        ]);

        return response()->json([
            'success' => true,
            'pedidos' => Pedido::mostrarPorFecha($datos['fecha']),
        ]);
    }

    public function mostrarPorEstado(Request $request)
    {
        $datos = $request->validate([
            'estado' => ['required', 'string', 'in:abierto,completado,cancelado'],
        ], [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        return response()->json([
            'success' => true,
            'pedidos' => Pedido::mostrarPorEstado($datos['estado']),
        ]);
    }

    // Reservas confirmadas para hoy que aún no tienen pedido.
    public function reservasPorAtender()
    {
        return response()->json([
            'success' => true,
            'reservas' => Pedido::reservasPorAtender(),
        ]);
    }

    public function insertarPedido(InsertarPedidoRequest $request)
    {
        $pedido = Pedido::insertar(
            $request->validated(),
            Auth::id()
        );

        $cliente = $pedido->cliente
            ? $pedido->cliente->codigo . ' (' . $pedido->cliente->nombre_completo . ')'
            : 'consumidor final';

        Bitacora::registrar(
            Bitacora::CREADO,
            'PEDIDOS',
            $pedido->id,
            'Se registró el pedido #' . $pedido->id . ' (' . $pedido->tipo . ')'
            . ' de ' . $cliente
            . ', orden ' . $pedido->orden->numero_orden
            . ', total $' . $pedido->total,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'El pedido se registró correctamente.',
            'pedido' => $pedido,
        ], 201);
    }

    // Cancelación del pedido (único cambio de estado manual).
    public function actualizarEstado(ActualizarEstadoPedidoRequest $request, $id)
    {
        $datos = $request->validated();

        $pedido = Pedido::cancelar(
            $id,
            Auth::id(),
            $datos['motivo'] ?? null
        );

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El pedido solicitado no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'PEDIDOS',
            $pedido->id,
            'Se canceló el pedido #' . $pedido->id
            . (!empty($datos['motivo']) ? ': ' . $datos['motivo'] : ''),
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'El pedido fue cancelado y las existencias fueron restituidas correctamente.',
            'pedido' => $pedido,
        ]);
    }
}
