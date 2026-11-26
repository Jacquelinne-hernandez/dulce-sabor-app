<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarRolRequest;
use App\Http\Requests\InsertarRolRequest;
use App\Models\Bitacora;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RolController extends Controller
{
    // Mostrar la vista principal
    public function index(): View
    {
        return view('rol.roles');
    }

    // Obtener todos los roles en formato JSON
    public function mostrarTodos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'roles' => Rol::mostrarTodos()
        ], 200);
    }

    // Insertar un nuevo rol
    public function insertarRol(InsertarRolRequest $request): JsonResponse
    {
        $rol = Rol::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'ROLES',
            $rol->id,
            'Se registró el rol: ' . $rol->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Rol registrado correctamente.',
            'rol' => $rol
        ], 201);
    }

    // Buscar un rol por su ID
    public function buscarRol($id): JsonResponse
    {
        $rol = Rol::buscarRolXId($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el rol solicitado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'rol' => $rol
        ], 200);
    }

    // Actualizar un rol existente
    public function actualizarRol(ActualizarRolRequest $request, $id): JsonResponse
    {
        $rol = Rol::actualizar($id, $request->validated());

        if (!$rol) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el rol solicitado.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'ROLES',
            $rol->id,
            'Se actualizó el rol: ' . $rol->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Rol actualizado correctamente.',
            'rol' => $rol
        ], 200);
    }

    // Eliminar un rol (solo si no es base y no tiene usuarios)
    public function eliminarRol($id): JsonResponse
    {
        $rol = Rol::buscarRolXId($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el rol solicitado.'
            ], 404);
        }

        $nombre = $rol->nombre;

        Rol::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'ROLES',
            $rol->id,
            'Se eliminó el rol: ' . $nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Rol eliminado correctamente.'
        ], 200);
    }
}
