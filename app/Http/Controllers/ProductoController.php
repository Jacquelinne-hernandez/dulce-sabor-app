<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarProductoRequest;
use App\Http\Requests\InsertarProductoRequest;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Gestión de productos (administrador)
    |
    | Solo aquí se envía el costo: el modelo lo oculta por defecto.
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view('producto.productos', compact('categorias'));
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'productos' => Producto::mostrarTodos()->makeVisible('costo'),
        ]);
    }

    public function insertarProducto(InsertarProductoRequest $request)
    {
        $producto = Producto::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'PRODUCTOS',
            $producto->id,
            'Se registró el producto: ' . $producto->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto registrado correctamente.',
            'producto' => $producto->load('categoria')->makeVisible('costo'),
        ], 201);
    }

    public function buscarProducto($id)
    {
        $producto = Producto::buscarProductoXId($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El producto solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'producto' => $producto->makeVisible('costo'),
        ]);
    }

    public function actualizarProducto(ActualizarProductoRequest $request, $id)
    {
        $producto = Producto::actualizar($id, $request->validated());

        if (!$producto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El producto no existe o se encuentra inactivo.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'PRODUCTOS',
            $producto->id,
            'Se actualizó el producto: ' . $producto->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto actualizado correctamente.',
            'producto' => $producto->makeVisible('costo'),
        ]);
    }

    public function eliminarProducto($id)
    {
        $producto = Producto::buscarProductoXId($id);

        if (!$producto || $producto->estado === 'inactivo') {
            return response()->json([
                'success' => false,
                'mensaje' => 'El producto no existe o ya se encuentra inactivo.',
            ], 404);
        }

        Producto::eliminar($id);

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'PRODUCTOS',
            $producto->id,
            'Se inactivó el producto: ' . $producto->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto inactivado correctamente.',
        ]);
    }

    public function reactivarProducto($id)
    {
        $producto = Producto::reactivar($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El producto no existe o no se encuentra inactivo.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'PRODUCTOS',
            $producto->id,
            'Se reactivó el producto: ' . $producto->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto reactivado correctamente.',
            'producto' => $producto->makeVisible('costo'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Catálogo de solo lectura (administrador y operador)
    |
    | Muestra precio y existencias, sin costo y sin opciones de edición.
    |--------------------------------------------------------------------------
    */

    public function catalogo()
    {
        return view('producto.catalogo');
    }

    public function catalogoTodos()
    {
        return response()->json([
            'success' => true,
            'productos' => Producto::mostrarCatalogo(),
        ]);
    }
}
