<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/*
 * Los estados son fijos (los crea EstadoSeeder): esta pantalla es solo de
 * consulta. No hay rutas para crearlos, editarlos ni eliminarlos.
 */
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
}
