<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarProveedorRequest;
use App\Http\Requests\InsertarProveedorRequest;
use App\Models\Bitacora;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Vista principal
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('proveedor.proveedores');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar todos los proveedores
    |--------------------------------------------------------------------------
    */

    public function mostrarTodos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'proveedores' => Proveedor::mostrarTodos()
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar proveedor
    |--------------------------------------------------------------------------
    */

    public function insertarProveedor(InsertarProveedorRequest $request): JsonResponse
    {
        $proveedor = Proveedor::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'PROVEEDORES',
            $proveedor->id,
            'Se registró el proveedor: ' . $proveedor->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Proveedor registrado correctamente.',
            'proveedor' => $proveedor
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar proveedor por ID
    |--------------------------------------------------------------------------
    */

    public function buscarProveedor($id): JsonResponse
    {
        $proveedor = Proveedor::buscarProveedorXId($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El proveedor solicitado no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'proveedor' => $proveedor
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar proveedor
    |--------------------------------------------------------------------------
    */

    public function actualizarProveedor(
        ActualizarProveedorRequest $request,
                                   $id
    ): JsonResponse {
        $proveedor = Proveedor::actualizar($id, $request->validated());

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El proveedor solicitado no existe.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'PROVEEDORES',
            $proveedor->id,
            'Se actualizaron los datos del proveedor: ' . $proveedor->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Proveedor actualizado correctamente.',
            'proveedor' => $proveedor
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar proveedor (solo si no tiene compras)
    |--------------------------------------------------------------------------
    */

    public function eliminarProveedor($id): JsonResponse
    {
        $proveedor = Proveedor::buscarProveedorXId($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El proveedor solicitado no existe.'
            ], 404);
        }

        Proveedor::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'PROVEEDORES',
            $proveedor->id,
            'Se eliminó el proveedor: ' . $proveedor->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Proveedor eliminado correctamente.'
        ], 200);
    }
}
