<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\InsertarUsuarioRequest;
use App\Models\Bitacora;
use App\Models\Estado;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    // Mostrar la vista principal (con los datos de los selectores)
    public function index(): View
    {
        return view('usuario.usuarios', [
            'roles' => Rol::mostrarTodos(),
            'estados' => Estado::mostrarAsignables(),
        ]);
    }

    // Obtener todos los usuarios en formato JSON
    public function mostrarTodos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'usuarios' => Usuario::mostrarTodos()
        ], 200);
    }

    // Insertar un nuevo usuario
    public function insertarUsuario(InsertarUsuarioRequest $request): JsonResponse
    {
        $usuario = Usuario::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'USUARIOS',
            $usuario->id,
            'Se registró el usuario: ' . $usuario->nombre . ' ' . $usuario->apellido
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario registrado correctamente.',
            'usuario' => $usuario->load(['rol', 'estado'])
        ], 201);
    }

    // Buscar un usuario por su ID
    public function buscarUsuario($id): JsonResponse
    {
        $usuario = Usuario::buscarUsuarioXId($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el usuario solicitado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'usuario' => $usuario
        ], 200);
    }

    // Actualizar un usuario existente
    public function actualizarUsuario(ActualizarUsuarioRequest $request, $id): JsonResponse
    {
        $datos = $request->validated();

        // Evitar que el administrador se bloquee a sí mismo.
        if ((int) $id === (int) Auth::id()) {
            if ((int) $datos['id_estado'] !== Estado::ACTIVO) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No puede cambiar el estado de su propia cuenta.'
                ], 422);
            }

            if ((int) $datos['id_rol'] !== (int) Auth::user()->id_rol) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No puede cambiar el rol de su propia cuenta.'
                ], 422);
            }
        }

        $usuario = Usuario::actualizar($id, $datos);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el usuario solicitado.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'USUARIOS',
            $usuario->id,
            'Se actualizó el usuario: ' . $usuario->nombre . ' ' . $usuario->apellido
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario actualizado correctamente.',
            'usuario' => $usuario->load(['rol', 'estado'])
        ], 200);
    }

    // Eliminar un usuario (eliminación lógica)
    public function eliminarUsuario($id): JsonResponse
    {
        if ((int) $id === (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No puede eliminar su propia cuenta.'
            ], 422);
        }

        $usuario = Usuario::buscarUsuarioXId($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró el usuario solicitado.'
            ], 404);
        }

        $nombre = $usuario->nombre . ' ' . $usuario->apellido;

        Usuario::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'USUARIOS',
            $usuario->id,
            'Se eliminó el usuario: ' . $nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario eliminado correctamente.'
        ], 200);
    }
}
