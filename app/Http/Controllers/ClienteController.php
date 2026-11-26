<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarClienteRequest;
use App\Http\Requests\InsertarClienteRequest;
use App\Models\Bitacora;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClienteController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Vista principal
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('cliente.clientes');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar todos los clientes
    |--------------------------------------------------------------------------
    */

    public function mostrarTodos(): JsonResponse
    {
        $clientes = Cliente::mostrarTodos();

        return response()->json([
            'success' => true,
            'clientes' => $clientes
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar cliente
    |--------------------------------------------------------------------------
    */

    public function insertarCliente(InsertarClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'CLIENTES',
            $cliente->id,
            'Se registró el cliente: ' . $cliente->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente registrado correctamente.',
            'cliente' => $cliente
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar cliente por ID
    |--------------------------------------------------------------------------
    */

    public function buscarCliente($id): JsonResponse
    {
        $cliente = Cliente::buscarClienteXId($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El cliente solicitado no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'cliente' => $cliente
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar cliente
    |--------------------------------------------------------------------------
    */

    public function actualizarCliente(
        ActualizarClienteRequest $request,
                                 $id
    ): JsonResponse {
        $cliente = Cliente::actualizar($id, $request->validated());

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El cliente no existe o se encuentra eliminado.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'CLIENTES',
            $cliente->id,
            'Se actualizaron los datos del cliente: ' . $cliente->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente actualizado correctamente.',
            'cliente' => $cliente
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar cliente (lógico)
    |--------------------------------------------------------------------------
    */

    public function eliminarCliente($id): JsonResponse
    {
        $cliente = Cliente::buscarClienteXId($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El cliente no existe o ya se encuentra eliminado.'
            ], 404);
        }

        Cliente::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'CLIENTES',
            $cliente->id,
            'Se cambió el estado del cliente a eliminado: ' . $cliente->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente eliminado correctamente.'
        ], 200);
    }
}
