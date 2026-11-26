<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarCategoriaRequest;
use App\Http\Requests\InsertarCategoriaRequest;
use App\Models\Bitacora;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Vista principal
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('categoria.categorias');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar todas las categorías
    |--------------------------------------------------------------------------
    */

    public function mostrarTodos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'categorias' => Categoria::mostrarTodos()
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar categoría
    |--------------------------------------------------------------------------
    */

    public function insertarCategoria(InsertarCategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'CATEGORIAS',
            $categoria->id,
            'Se registró la categoría: ' . $categoria->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Categoría registrada correctamente.',
            'categoria' => $categoria
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar categoría por ID
    |--------------------------------------------------------------------------
    */

    public function buscarCategoria($id): JsonResponse
    {
        $categoria = Categoria::buscarCategoriaXId($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La categoría solicitada no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'categoria' => $categoria
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar categoría
    |--------------------------------------------------------------------------
    */

    public function actualizarCategoria(
        ActualizarCategoriaRequest $request,
                                   $id
    ): JsonResponse {
        $categoria = Categoria::actualizar($id, $request->validated());

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La categoría solicitada no existe.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'CATEGORIAS',
            $categoria->id,
            'Se actualizó la categoría: ' . $categoria->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Categoría actualizada correctamente.',
            'categoria' => $categoria
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar categoría (solo si no tiene productos)
    |--------------------------------------------------------------------------
    */

    public function eliminarCategoria($id): JsonResponse
    {
        $categoria = Categoria::buscarCategoriaXId($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La categoría solicitada no existe.'
            ], 404);
        }

        Categoria::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'CATEGORIAS',
            $categoria->id,
            'Se eliminó la categoría: ' . $categoria->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Categoría eliminada correctamente.'
        ], 200);
    }
}
