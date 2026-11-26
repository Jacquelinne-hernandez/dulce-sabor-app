<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarCompraRequest;
use App\Models\Bitacora;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;

class CompraController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        $productos = Producto::where('estado', '!=', 'inactivo')
            ->orderBy('nombre')
            ->get();

        return view('compra.compras', compact(
            'proveedores',
            'productos'
        ));
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'compras' => Compra::mostrarTodos(),
        ]);
    }

    public function buscarCompra($id)
    {
        $compra = Compra::buscarCompraXId($id);

        if (!$compra) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La compra solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'compra' => $compra,
        ]);
    }

    public function insertarCompra(InsertarCompraRequest $request)
    {
        $compra = Compra::insertar(
            $request->validated(),
            Auth::id()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'COMPRAS',
            $compra->id,
            'Se registró una compra por un total de $' . $compra->total,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Compra registrada correctamente. El inventario ha sido actualizado.',
            'compra' => $compra,
        ], 201);
    }
}

