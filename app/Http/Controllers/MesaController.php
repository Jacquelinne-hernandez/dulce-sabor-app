<?php

namespace App\Http\Controllers;
use App\Http\Requests\ActualizarMesaRequest;
use App\Http\Requests\CambiarEstadoMesaRequest;
use App\Http\Requests\InsertarMesaRequest;
use App\Models\Bitacora;
use App\Models\Mesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MesaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Vista principal
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('mesa.mesas');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar todas las mesas
    |--------------------------------------------------------------------------
    */

    public function mostrarTodos(): JsonResponse
    {
        $mesas = Mesa::mostrarTodos();

        return response()->json([
            'success' => true,
            'mesas' => $mesas
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar mesa
    |--------------------------------------------------------------------------
    */

    public function insertarMesa(InsertarMesaRequest $request): JsonResponse
    {
        $mesa = Mesa::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'MESAS',
            $mesa->id,
            'Se registró la mesa número: ' . $mesa->numero,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Mesa registrada correctamente.',
            'mesa' => $mesa
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar mesa por ID
    |--------------------------------------------------------------------------
    */

    public function buscarMesa($id): JsonResponse
    {
        $mesa = Mesa::buscarMesaXId($id);

        if (!$mesa) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La mesa solicitada no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'mesa' => $mesa
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar datos de la mesa
    |--------------------------------------------------------------------------
    */

    public function actualizarMesa(
        ActualizarMesaRequest $request,
                              $id
    ): JsonResponse {
        $mesa = Mesa::actualizar($id, $request->validated());

        if (!$mesa) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La mesa solicitada no existe.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'MESAS',
            $mesa->id,
            'Se actualizaron los datos de la mesa número: ' . $mesa->numero,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Mesa actualizada correctamente.',
            'mesa' => $mesa
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Cambiar estado de la mesa
    |--------------------------------------------------------------------------
    */

    public function cambiarEstado(CambiarEstadoMesaRequest $request, $id): JsonResponse
    {
        $mesa = Mesa::cambiarEstado(
            $id,
            $request->validated()['estado']
        );

        if (!$mesa) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La mesa solicitada no existe.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'MESAS',
            $mesa->id,
            'Se cambió el estado de la mesa número ' .
            $mesa->numero . ' a: ' . $mesa->estado,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Estado de la mesa actualizado correctamente.',
            'mesa' => $mesa
        ], 200);
    }
    /*
    |--------------------------------------------------------------------------
    | Mostrar mesas por estado
    |--------------------------------------------------------------------------
    */

    public function mostrarPorEstado($estado): JsonResponse
    {
        $mesas = Mesa::mostrarPorEstado($estado);

        return response()->json([
            'success' => true,
            'mesas' => $mesas
        ], 200);
    }
}
