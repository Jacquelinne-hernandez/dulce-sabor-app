<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEstadoOrdenRequest;
use App\Models\Bitacora;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdenController extends Controller
{
    public function index()
    {
        return view('orden.ordenes');
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'ordenes' => Orden::mostrarTodos(),
        ]);
    }

    // Órdenes por atender (pendientes, en producción y listas).
    public function mostrarActivas()
    {
        return response()->json([
            'success' => true,
            'ordenes' => Orden::mostrarActivas(),
        ]);
    }

    public function buscarOrden($id)
    {
        $orden = Orden::buscarOrdenXId($id);

        if (!$orden) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La orden solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'orden' => $orden,
        ]);
    }

    public function mostrarPorEstado(Request $request)
    {
        $datos = $request->validate([
            'estado' => [
                'required',
                'string',
                'in:pendiente,en_produccion,lista,entregada,cancelada',
            ],
        ], [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        return response()->json([
            'success' => true,
            'ordenes' => Orden::mostrarPorEstado($datos['estado']),
        ]);
    }

    public function actualizarEstado(ActualizarEstadoOrdenRequest $request, $id)
    {
        $anterior = Orden::find($id);

        if (!$anterior) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La orden solicitada no existe.',
            ], 404);
        }

        $estadoAnterior = $anterior->estado;

        $orden = Orden::cambiarEstado(
            $id,
            $request->validated()['estado'],
            Auth::id()
        );

        if (!$orden) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La orden solicitada no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'ORDENES',
            $orden->id,
            'La orden ' . $orden->numero_orden . ' pasó de '
            . $estadoAnterior . ' a ' . $orden->estado,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Estado de la orden actualizado correctamente.',
            'orden' => $orden,
        ]);
    }
}
