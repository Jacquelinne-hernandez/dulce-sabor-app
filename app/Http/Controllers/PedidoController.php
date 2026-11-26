<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsertarPedidoRequest;
use App\Http\Requests\ActualizarEstadoPedidoRequest;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PedidoController extends Controller
{
    public function index()
    {
        $clientes = Cliente::where('id_estado', '!=', 3)
            ->orderBy('nombre')
            ->get();

        $mesas = Mesa::where('estado', '!=', 'mantenimiento')
            ->orderBy('numero')
            ->get();

        $productos = Producto::where('estado', 'disponible')
            ->where('stock', '>', 0)
            ->orderBy('nombre')
            ->get();

        return view('pedido.pedidos', compact(
            'clientes',
            'mesas',
            'productos'
        ));
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
        $datos = validator([
            'id' => $id,
        ], [
            'id' => [
                'required',
                'integer',
                'exists:pedidos,id',
            ],
        ])->validate();

        $pedido = Pedido::buscarPedidoXId($datos['id']);

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
            'estado' => Pedido::reglasEstado()['estado'],
        ], [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        return response()->json([
            'success' => true,
            'pedidos' => Pedido::mostrarPorEstado($datos['estado']),
        ]);
    }

    public function insertarPedido(InsertarPedidoRequest $request)
    {
        $datos = $request->validated();

        $pedido = Pedido::insertar(
            $datos,
            Auth::id()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'PEDIDOS',
            $pedido->id,
            'Se registró el pedido #' . $pedido->id,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'El pedido se registró correctamente.',
            'pedido' => $pedido,
        ], 201);
    }

    public function actualizarEstado(
        ActualizarEstadoPedidoRequest $request,
                                      $id
    ) {
        $datos = $request->validated();

        $pedido = Pedido::cambiarEstado(
            $id,
            $datos['estado']
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
            'Se actualizó el estado del pedido #' . $pedido->id .
            ' a ' . $pedido->estado,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => $pedido->estado === 'cancelado'
                ? 'El pedido fue cancelado y las existencias fueron restituidas correctamente.'
                : 'El estado del pedido se actualizó correctamente.',
            'pedido' => $pedido,
        ]);
    }
}
