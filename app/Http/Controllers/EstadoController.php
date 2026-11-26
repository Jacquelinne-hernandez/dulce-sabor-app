<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEstadoRequest;
use App\Http\Requests\InsertarEstadoRequest;
use App\Models\Bitacora;
use App\Models\Estado;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EstadoController extends Controller
{
    // Mostrar la vista principal
    public function index(): View
    {
        return view('estado.estados');
    }

    // Obtener todos los estados en formato JSON
    public function mostrarTodos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'estados' => Estado::mostrarTodos()
        ], 200);
    }

    // Insertar un nuevo estado
    public function insertarEstado(InsertarEstadoRequest $request): JsonResponse
    {
        $estado = Estado::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'ESTADOS',
            $estado->id,
            'Se registró el estado: ' . $estado->estado,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Estado registrado correctamente.',
            'estado' => $estado
        ], 201);
    }

    // Buscar un estado por su ID
    public function buscarEstado($id): JsonResponse
    {
        $estado = Estado::buscarEstadoXId($id);

        if (!$estado) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el estado solicitado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'estado' => $estado
        ], 200);
    }

    // Actualizar un estado existente
    public function actualizarEstado(
        ActualizarEstadoRequest $request,
                                $id
    ): JsonResponse {
        $estado = Estado::actualizar($id, $request->validated());

        if (!$estado) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el estado solicitado.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'ESTADOS',
            $estado->id,
            'Se actualizó el estado: ' . $estado->estado,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Estado actualizado correctamente.',
            'estado' => $estado
        ], 200);
    }

    // Eliminar un estado (solo si no es base y no está en uso)
    public function eliminarEstado($id): JsonResponse
    {
        $estado = Estado::buscarEstadoXId($id);

        if (!$estado) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el estado solicitado.'
            ], 404);
        }

        $nombre = $estado->estado;

        Estado::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'ESTADOS',
            $estado->id,
            'Se eliminó el estado: ' . $nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Estado eliminado correctamente.'
        ], 200);
    }
}
