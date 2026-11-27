<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/*
 * Los roles son fijos (los crea RolSeeder): de ellos dependen los permisos
 * de las rutas. Esta pantalla es solo de consulta.
 */
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
}
