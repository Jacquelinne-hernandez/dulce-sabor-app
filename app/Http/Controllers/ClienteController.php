<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarClienteRequest;
use App\Http\Requests\InsertarClienteRequest;
use App\Models\Bitacora;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'Se registró el cliente ' . $cliente->codigo . ': ' . $cliente->nombre_completo,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente registrado correctamente. Código asignado: ' . $cliente->codigo . '.',
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
    | Buscar cliente por código (tarjeta del cliente)
    |--------------------------------------------------------------------------
    */

    public function buscarPorCodigo($codigo): JsonResponse
    {
        $cliente = Cliente::buscarClienteXCodigo($codigo);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No existe un cliente activo con ese código.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'cliente' => $cliente
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar clientes por código o nombre (para asociarlos a una venta)
    |--------------------------------------------------------------------------
    */

    public function buscarPorTermino(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:60'],
        ], [
            'q.required' => 'Escriba el código o el nombre del cliente.',
            'q.string' => 'El criterio de búsqueda no es válido.',
            'q.min' => 'Escriba al menos 2 caracteres para buscar.',
            'q.max' => 'El criterio de búsqueda es demasiado largo.',
        ]);

        return response()->json([
            'success' => true,
            'clientes' => Cliente::buscarActivos($datos['q'])
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
            'Se actualizaron los datos del cliente ' . $cliente->codigo . ': ' . $cliente->nombre_completo,
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
        if (auth()->user()?->rol?->nombre !== 'Administrador') {
            return response()->json([
                'success' => false,
                'mensaje' => 'Solo un administrador puede eliminar clientes.'
            ], 403);
        }        $cliente = Cliente::buscarClienteXId($id);

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
            'Se cambió el estado del cliente a eliminado: ' . $cliente->codigo . ' ' . $cliente->nombre_completo,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente eliminado correctamente.'
        ], 200);
    }
}
