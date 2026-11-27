<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarProductoRequest;
use App\Http\Requests\InsertarProductoRequest;
use App\Http\Requests\SubirImagenProductoRequest;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
    | Imágenes de los productos
    |
    | Los archivos viven en storage/app/files/productos (fuera de public) y
    | se entregan por esta ruta solo a usuarios con sesión activa.
    |--------------------------------------------------------------------------
    */

    // Placeholder que se muestra cuando la imagen no existe o se perdió:
    // el sistema nunca devuelve error por una foto faltante.
    private const IMAGEN_PERDIDA = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'
        . '<rect width="120" height="120" fill="#f3e8f8"/>'
        . '<path d="M30 78h60v14a6 6 0 0 1-6 6H36a6 6 0 0 1-6-6z" fill="#c9a2dc"/>'
        . '<path d="M36 78V58a24 24 0 0 1 48 0v20z" fill="#e3c5f0"/>'
        . '<circle cx="60" cy="30" r="5" fill="#d6336c"/></svg>';

    public function imagen($id)
    {
        $ruta = Producto::whereKey($id)->value('imagen');

        if ($ruta && str_starts_with($ruta, Producto::CARPETA_IMAGENES . '/')) {
            $disco = Storage::disk(Producto::DISCO);

            if ($disco->exists($ruta)) {
                return $disco->response($ruta, null, [
                    'Cache-Control' => 'private, max-age=86400',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        return response(self::IMAGEN_PERDIDA, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function subirImagen(SubirImagenProductoRequest $request, $id)
    {
        $producto = Producto::buscarProductoXId($id);

        if (!$producto || $producto->estado === 'inactivo') {
            return response()->json([
                'success' => false,
                'mensaje' => 'El producto no existe o se encuentra inactivo.',
            ], 404);
        }

        Producto::guardarImagen($producto, $request->file('imagen'));

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'PRODUCTOS',
            $producto->id,
            'Se actualizó la imagen del producto: ' . $producto->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Imagen guardada correctamente.',
            'producto' => $producto->fresh('categoria')->makeVisible('costo'),
        ]);
    }

    public function quitarImagen($id)
    {
        $producto = Producto::buscarProductoXId($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El producto solicitado no existe.',
            ], 404);
        }

        Producto::quitarImagen($producto);

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'PRODUCTOS',
            $producto->id,
            'Se quitó la imagen del producto: ' . $producto->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Imagen eliminada correctamente.',
            'producto' => $producto->fresh('categoria')->makeVisible('costo'),
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

    // Una página del catálogo. Parámetros opcionales: q (texto),
    // disponibilidad (disponible|bajo|agotado|vendible), pagina y por_pagina.
    public function catalogoTodos(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'disponibilidad' => ['nullable', 'string', 'in:disponible,bajo,agotado,vendible'],
            'pagina' => ['nullable', 'integer', 'min:1'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:' . Producto::CATALOGO_POR_PAGINA_MAX],
        ], [
            'q.max' => 'La búsqueda no debe superar los 100 caracteres.',
            'disponibilidad.in' => 'El filtro de existencias no es válido.',
            'pagina.integer' => 'La página solicitada no es válida.',
            'por_pagina.max' => 'No se pueden pedir tantos productos por página.',
        ]);

        $pagina = Producto::paginarCatalogo($filtros);

        return response()->json([
            'success' => true,
            'productos' => $pagina->items(),
            'paginacion' => [
                'pagina' => $pagina->currentPage(),
                'por_pagina' => $pagina->perPage(),
                'total' => $pagina->total(),
                'ultima' => $pagina->lastPage(),
                'desde' => $pagina->firstItem() ?? 0,
                'hasta' => $pagina->lastItem() ?? 0,
            ],
        ]);
    }
}
