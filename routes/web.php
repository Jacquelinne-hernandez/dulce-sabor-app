<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/

Route::get('login', [LoginController::class, 'index'])->name('login');
Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,3');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Cualquier usuario autenticado y activo (Administrador u Operador)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'rol'])->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('home/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Módulos operativos: Administrador y Operador
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'rol:Administrador,Operador'])->group(function () {

    // Clientes
    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes');
    Route::get('clientes/todos', [ClienteController::class, 'mostrarTodos'])->name('clientes.todos');
    Route::post('clientes/insertar', [ClienteController::class, 'insertarCliente'])->name('clientes.insertar');
    Route::get('clientes/buscar/{id}', [ClienteController::class, 'buscarCliente'])->name('clientes.buscar');
    Route::put('clientes/actualizar/{id}', [ClienteController::class, 'actualizarCliente'])->name('clientes.actualizar');
    Route::delete('clientes/eliminar/{id}', [ClienteController::class, 'eliminarCliente'])->name('clientes.eliminar');

    // Mesas (consulta y cambio de estado)
    Route::get('mesas', [MesaController::class, 'index'])->name('mesas');
    Route::get('mesas/todos', [MesaController::class, 'mostrarTodos'])->name('mesas.todos');
    Route::get('mesas/buscar/{id}', [MesaController::class, 'buscarMesa'])->name('mesas.buscar');
    Route::get('mesas/estado/{estado}', [MesaController::class, 'mostrarPorEstado'])->name('mesas.estado');
    Route::patch('mesas/cambiar-estado/{id}', [MesaController::class, 'cambiarEstado'])->name('mesas.cambiarEstado');

    // Reservas
    Route::get('reservas', [ReservaController::class, 'index'])->name('reservas');
    Route::get('reservas/todos', [ReservaController::class, 'mostrarTodos'])->name('reservas.todos');
    Route::get('reservas/buscar/{id}', [ReservaController::class, 'buscarReserva'])->name('reservas.buscar');
    Route::get('reservas/fecha', [ReservaController::class, 'mostrarPorFecha'])->name('reservas.fecha');
    Route::get('reservas/estado', [ReservaController::class, 'mostrarPorEstado'])->name('reservas.estado');
    Route::post('reservas/insertar', [ReservaController::class, 'insertarReserva'])->name('reservas.insertar');
    Route::patch('reservas/actualizar-estado/{id}', [ReservaController::class, 'actualizarEstado'])->name('reservas.actualizarEstado');

    // Pedidos
    Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos');
    Route::get('pedidos/todos', [PedidoController::class, 'mostrarTodos'])->name('pedidos.todos');
    Route::get('pedidos/buscar/{id}', [PedidoController::class, 'buscarPedido'])->name('pedidos.buscar');
    Route::get('pedidos/fecha', [PedidoController::class, 'mostrarPorFecha'])->name('pedidos.fecha');
    Route::get('pedidos/estado', [PedidoController::class, 'mostrarPorEstado'])->name('pedidos.estado');
    Route::post('pedidos/insertar', [PedidoController::class, 'insertarPedido'])->name('pedidos.insertar');
    Route::patch('pedidos/{id}/estado', [PedidoController::class, 'actualizarEstado'])->name('pedidos.actualizarEstado');

    // Facturas (emisión y consulta)
    Route::get('facturas', [FacturaController::class, 'index'])->name('facturas');
    Route::get('facturas/todos', [FacturaController::class, 'mostrarTodos'])->name('facturas.todos');
    Route::get('facturas/buscar/{id}', [FacturaController::class, 'buscarFactura'])->name('facturas.buscar');
    Route::post('facturas/insertar', [FacturaController::class, 'insertarFactura'])->name('facturas.insertar');
});

/*
|--------------------------------------------------------------------------
| Módulos administrativos: solo Administrador
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'rol:Administrador'])->group(function () {

    // Bitácora
    Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora');

    // Roles
    Route::get('roles', [RolController::class, 'index'])->name('roles');
    Route::get('roles/todos', [RolController::class, 'mostrarTodos'])->name('roles.todos');
    Route::post('roles/insertar', [RolController::class, 'insertarRol'])->name('roles.insertar');
    Route::get('roles/buscar/{id}', [RolController::class, 'buscarRol'])->name('roles.buscar');
    Route::put('roles/actualizar/{id}', [RolController::class, 'actualizarRol'])->name('roles.actualizar');
    Route::delete('roles/eliminar/{id}', [RolController::class, 'eliminarRol'])->name('roles.eliminar');

    // Estados
    Route::get('estados', [EstadoController::class, 'index'])->name('estados');
    Route::get('estados/todos', [EstadoController::class, 'mostrarTodos'])->name('estados.todos');
    Route::post('estados/insertar', [EstadoController::class, 'insertarEstado'])->name('estados.insertar');
    Route::get('estados/buscar/{id}', [EstadoController::class, 'buscarEstado'])->name('estados.buscar');
    Route::put('estados/actualizar/{id}', [EstadoController::class, 'actualizarEstado'])->name('estados.actualizar');
    Route::delete('estados/eliminar/{id}', [EstadoController::class, 'eliminarEstado'])->name('estados.eliminar');

    // Usuarios
    Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios');
    Route::get('usuarios/todos', [UsuarioController::class, 'mostrarTodos'])->name('usuarios.todos');
    Route::post('usuarios/insertar', [UsuarioController::class, 'insertarUsuario'])->name('usuarios.insertar');
    Route::get('usuarios/buscar/{id}', [UsuarioController::class, 'buscarUsuario'])->name('usuarios.buscar');
    Route::put('usuarios/actualizar/{id}', [UsuarioController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
    Route::delete('usuarios/eliminar/{id}', [UsuarioController::class, 'eliminarUsuario'])->name('usuarios.eliminar');

    // Categorías
    Route::get('categorias', [CategoriaController::class, 'index'])->name('categorias');
    Route::get('categorias/todos', [CategoriaController::class, 'mostrarTodos'])->name('categorias.todos');
    Route::post('categorias/insertar', [CategoriaController::class, 'insertarCategoria'])->name('categorias.insertar');
    Route::get('categorias/buscar/{id}', [CategoriaController::class, 'buscarCategoria'])->name('categorias.buscar');
    Route::put('categorias/actualizar/{id}', [CategoriaController::class, 'actualizarCategoria'])->name('categorias.actualizar');
    Route::delete('categorias/eliminar/{id}', [CategoriaController::class, 'eliminarCategoria'])->name('categorias.eliminar');

    // Proveedores
    Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores');
    Route::get('proveedores/todos', [ProveedorController::class, 'mostrarTodos'])->name('proveedores.todos');
    Route::post('proveedores/insertar', [ProveedorController::class, 'insertarProveedor'])->name('proveedores.insertar');
    Route::get('proveedores/buscar/{id}', [ProveedorController::class, 'buscarProveedor'])->name('proveedores.buscar');
    Route::put('proveedores/actualizar/{id}', [ProveedorController::class, 'actualizarProveedor'])->name('proveedores.actualizar');
    Route::delete('proveedores/eliminar/{id}', [ProveedorController::class, 'eliminarProveedor'])->name('proveedores.eliminar');

    // Productos
    Route::get('productos', [ProductoController::class, 'index'])->name('productos');
    Route::get('productos/todos', [ProductoController::class, 'mostrarTodos'])->name('productos.todos');
    Route::post('productos/insertar', [ProductoController::class, 'insertarProducto'])->name('productos.insertar');
    Route::get('productos/buscar/{id}', [ProductoController::class, 'buscarProducto'])->name('productos.buscar');
    Route::put('productos/actualizar/{id}', [ProductoController::class, 'actualizarProducto'])->name('productos.actualizar');
    Route::delete('productos/eliminar/{id}', [ProductoController::class, 'eliminarProducto'])->name('productos.eliminar');
    Route::patch('productos/reactivar/{id}', [ProductoController::class, 'reactivarProducto'])->name('productos.reactivar');

    // Compras
    Route::get('compras', [CompraController::class, 'index'])->name('compras');
    Route::get('compras/todos', [CompraController::class, 'mostrarTodos'])->name('compras.todos');
    Route::get('compras/buscar/{id}', [CompraController::class, 'buscarCompra'])->name('compras.buscar');
    Route::post('compras/insertar', [CompraController::class, 'insertarCompra'])->name('compras.insertar');

    // Horarios
    Route::get('horarios', [HorarioController::class, 'index'])->name('horarios');
    Route::get('horarios/todos', [HorarioController::class, 'mostrarTodos'])->name('horarios.todos');
    Route::get('horarios/buscar/{id}', [HorarioController::class, 'buscarHorario'])->name('horarios.buscar');
    Route::get('horarios/dia/{dia}', [HorarioController::class, 'buscarHorarioXDia'])->name('horarios.dia');
    Route::put('horarios/actualizar/{id}', [HorarioController::class, 'actualizarHorario'])->name('horarios.actualizar');

    // Mesas (alta y edición)
    Route::post('mesas/insertar', [MesaController::class, 'insertarMesa'])->name('mesas.insertar');
    Route::put('mesas/actualizar/{id}', [MesaController::class, 'actualizarMesa'])->name('mesas.actualizar');

    // Facturas (anulación)
    Route::patch('facturas/anular/{id}', [FacturaController::class, 'anularFactura'])->name('facturas.anular');
});
