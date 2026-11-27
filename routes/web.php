<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PasswordController;
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

    // Cada rol ve su propio dashboard (el administrador admite ?desde=&hasta=).
    Route::get('home/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    // Cambio de la propia contraseña. Es lo único que puede abrir un usuario
    // con cambio obligatorio pendiente (ver RolMiddleware).
    Route::get('cuenta/password', [PasswordController::class, 'formulario'])->name('password.cambiar');
    Route::put('cuenta/password', [PasswordController::class, 'actualizar'])
        ->middleware('throttle:6,1')
        ->name('password.actualizar');
});

/*
|--------------------------------------------------------------------------
| Operación: Administrador y Operador
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'rol:Administrador,Operador'])->group(function () {

    // Clientes
    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes');
    Route::get('clientes/todos', [ClienteController::class, 'mostrarTodos'])->name('clientes.todos');
    Route::get('clientes/codigo/{codigo}', [ClienteController::class, 'buscarPorCodigo'])->name('clientes.codigo');
    Route::get('clientes/filtrar', [ClienteController::class, 'buscarPorTermino'])->name('clientes.filtrar');
    Route::post('clientes/insertar', [ClienteController::class, 'insertarCliente'])->name('clientes.insertar');
    Route::get('clientes/buscar/{id}', [ClienteController::class, 'buscarCliente'])->name('clientes.buscar');
    Route::put('clientes/actualizar/{id}', [ClienteController::class, 'actualizarCliente'])->name('clientes.actualizar');

    // Catálogo de productos (solo lectura: existencias y precio, sin costo)
    Route::get('productos/catalogo', [ProductoController::class, 'catalogo'])->name('productos.catalogo');
    Route::get('productos/catalogo/todos', [ProductoController::class, 'catalogoTodos'])->name('productos.catalogo.todos');

    // Imagen de un producto (se entrega desde storage/app/files)
    Route::get('productos/imagen/{id}', [ProductoController::class, 'imagen'])
        ->whereNumber('id')
        ->name('productos.imagen');

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
    Route::get('reservas/mesas-disponibles', [ReservaController::class, 'mesasDisponibles'])->name('reservas.mesasDisponibles');
    Route::get('reservas/cotizar', [ReservaController::class, 'cotizar'])->name('reservas.cotizar');
    Route::post('reservas/insertar', [ReservaController::class, 'insertarReserva'])->name('reservas.insertar');
    Route::patch('reservas/actualizar-estado/{id}', [ReservaController::class, 'actualizarEstado'])->name('reservas.actualizarEstado');

    // Pedidos
    Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos');
    Route::get('pedidos/todos', [PedidoController::class, 'mostrarTodos'])->name('pedidos.todos');
    Route::get('pedidos/buscar/{id}', [PedidoController::class, 'buscarPedido'])->name('pedidos.buscar');
    Route::get('pedidos/fecha', [PedidoController::class, 'mostrarPorFecha'])->name('pedidos.fecha');
    Route::get('pedidos/estado', [PedidoController::class, 'mostrarPorEstado'])->name('pedidos.estado');
    Route::get('pedidos/reservas-por-atender', [PedidoController::class, 'reservasPorAtender'])->name('pedidos.reservasPorAtender');
    Route::post('pedidos/insertar', [PedidoController::class, 'insertarPedido'])->name('pedidos.insertar');
    // Un pedido solo se cancela (estado = cancelado); se completa al pagarse.
    Route::patch('pedidos/{id}/estado', [PedidoController::class, 'actualizarEstado'])
        ->whereNumber('id')
        ->name('pedidos.actualizarEstado');

    // Órdenes de trabajo
    Route::get('ordenes', [OrdenController::class, 'index'])->name('ordenes');
    Route::get('ordenes/todos', [OrdenController::class, 'mostrarTodos'])->name('ordenes.todos');
    Route::get('ordenes/activas', [OrdenController::class, 'mostrarActivas'])->name('ordenes.activas');
    Route::get('ordenes/estado', [OrdenController::class, 'mostrarPorEstado'])->name('ordenes.estado');
    Route::get('ordenes/buscar/{id}', [OrdenController::class, 'buscarOrden'])->name('ordenes.buscar');
    Route::patch('ordenes/{id}/estado', [OrdenController::class, 'actualizarEstado'])
        ->whereNumber('id')
        ->name('ordenes.actualizarEstado');

    // Facturas (emisión, consulta e impresión)
    Route::get('facturas', [FacturaController::class, 'index'])->name('facturas');
    Route::get('facturas/todos', [FacturaController::class, 'mostrarTodos'])->name('facturas.todos');
    Route::get('facturas/estado', [FacturaController::class, 'mostrarPorEstado'])->name('facturas.estado');
    Route::get('facturas/por-facturar', [FacturaController::class, 'pedidosPorFacturar'])->name('facturas.porFacturar');
    Route::get('facturas/cotizar', [FacturaController::class, 'cotizar'])->name('facturas.cotizar');
    Route::get('facturas/buscar/{id}', [FacturaController::class, 'buscarFactura'])->name('facturas.buscar');
    Route::post('facturas/insertar', [FacturaController::class, 'insertarFactura'])->name('facturas.insertar');

    // Documentos imprimibles (PDF): factura completa y ticket
    Route::get('facturas/{id}/pdf', [DocumentoController::class, 'factura'])
        ->whereNumber('id')
        ->name('facturas.pdf');
    Route::get('facturas/{id}/ticket', [DocumentoController::class, 'ticket'])
        ->whereNumber('id')
        ->name('facturas.ticket');

    // Pagos (efectivo y tarjeta simulada)
    Route::get('facturas/{id}/pagos', [PagoController::class, 'mostrarPorFactura'])
        ->whereNumber('id')
        ->name('facturas.pagos');
    Route::post('pagos/insertar', [PagoController::class, 'registrarPago'])->name('pagos.insertar');
});

/*
|--------------------------------------------------------------------------
| Administración: solo Administrador
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'rol:Administrador'])->group(function () {

    // Clientes: eliminar (solo administrador; el operador crea y edita)
    Route::delete('clientes/eliminar/{id}', [ClienteController::class, 'eliminarCliente'])->name('clientes.eliminar');

    // Bitácora
    Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora');

    // Roles y estados: fijos (los crean RolSeeder y EstadoSeeder), solo consulta.
    Route::get('roles', [RolController::class, 'index'])->name('roles');
    Route::get('roles/todos', [RolController::class, 'mostrarTodos'])->name('roles.todos');
    Route::get('roles/buscar/{id}', [RolController::class, 'buscarRol'])->name('roles.buscar');

    Route::get('estados', [EstadoController::class, 'index'])->name('estados');
    Route::get('estados/todos', [EstadoController::class, 'mostrarTodos'])->name('estados.todos');
    Route::get('estados/buscar/{id}', [EstadoController::class, 'buscarEstado'])->name('estados.buscar');

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

    // Productos (gestión completa, con costo)
    Route::get('productos', [ProductoController::class, 'index'])->name('productos');
    Route::get('productos/todos', [ProductoController::class, 'mostrarTodos'])->name('productos.todos');
    Route::post('productos/insertar', [ProductoController::class, 'insertarProducto'])->name('productos.insertar');
    Route::get('productos/buscar/{id}', [ProductoController::class, 'buscarProducto'])->name('productos.buscar');
    Route::put('productos/actualizar/{id}', [ProductoController::class, 'actualizarProducto'])->name('productos.actualizar');
    Route::delete('productos/eliminar/{id}', [ProductoController::class, 'eliminarProducto'])->name('productos.eliminar');
    Route::patch('productos/reactivar/{id}', [ProductoController::class, 'reactivarProducto'])->name('productos.reactivar');
    Route::post('productos/{id}/imagen', [ProductoController::class, 'subirImagen'])
        ->whereNumber('id')
        ->name('productos.imagen.subir');
    Route::delete('productos/{id}/imagen', [ProductoController::class, 'quitarImagen'])
        ->whereNumber('id')
        ->name('productos.imagen.quitar');

    // Caja: dinero disponible y estado de cuenta
    Route::get('caja', [CajaController::class, 'index'])->name('caja');
    Route::get('caja/estado', [CajaController::class, 'estado'])->name('caja.estado');
    Route::get('caja/movimientos', [CajaController::class, 'movimientos'])->name('caja.movimientos');

    // Compras, deudas con proveedores y abonos
    Route::get('compras', [CompraController::class, 'index'])->name('compras');
    Route::get('compras/todos', [CompraController::class, 'mostrarTodos'])->name('compras.todos');
    Route::get('compras/deudas', [CompraController::class, 'mostrarDeudas'])->name('compras.deudas');
    Route::get('compras/buscar/{id}', [CompraController::class, 'buscarCompra'])->name('compras.buscar');
    Route::post('compras/insertar', [CompraController::class, 'insertarCompra'])->name('compras.insertar');
    Route::post('compras/{id}/abonos', [CompraController::class, 'registrarAbono'])
        ->whereNumber('id')
        ->name('compras.abonos');

    // Gastos adicionales (arrendamiento, luz, agua, internet...)
    Route::get('gastos', [GastoController::class, 'index'])->name('gastos');
    Route::get('gastos/todos', [GastoController::class, 'mostrarTodos'])->name('gastos.todos');
    Route::get('gastos/resumen', [GastoController::class, 'resumen'])->name('gastos.resumen');
    Route::get('gastos/buscar/{id}', [GastoController::class, 'buscarGasto'])->name('gastos.buscar');
    Route::post('gastos/insertar', [GastoController::class, 'insertarGasto'])->name('gastos.insertar');
    Route::put('gastos/actualizar/{id}', [GastoController::class, 'actualizarGasto'])->name('gastos.actualizar');
    Route::patch('gastos/anular/{id}', [GastoController::class, 'anularGasto'])->name('gastos.anular');

    // Ingresos externos al giro del negocio (capital, inversiones, aportes...)
    Route::get('ingresos', [IngresoController::class, 'index'])->name('ingresos');
    Route::get('ingresos/todos', [IngresoController::class, 'mostrarTodos'])->name('ingresos.todos');
    Route::get('ingresos/resumen', [IngresoController::class, 'resumen'])->name('ingresos.resumen');
    Route::get('ingresos/buscar/{id}', [IngresoController::class, 'buscarIngreso'])->name('ingresos.buscar');
    Route::post('ingresos/insertar', [IngresoController::class, 'insertarIngreso'])->name('ingresos.insertar');
    Route::put('ingresos/actualizar/{id}', [IngresoController::class, 'actualizarIngreso'])->name('ingresos.actualizar');
    Route::patch('ingresos/anular/{id}', [IngresoController::class, 'anularIngreso'])->name('ingresos.anular');

    // Horarios
    Route::get('horarios', [HorarioController::class, 'index'])->name('horarios');
    Route::get('horarios/todos', [HorarioController::class, 'mostrarTodos'])->name('horarios.todos');
    Route::get('horarios/buscar/{id}', [HorarioController::class, 'buscarHorario'])->name('horarios.buscar');
    Route::get('horarios/dia/{dia}', [HorarioController::class, 'buscarHorarioXDia'])->name('horarios.dia');
    Route::put('horarios/actualizar/{id}', [HorarioController::class, 'actualizarHorario'])->name('horarios.actualizar');

    // Mesas (alta, edición y tarifa por hora)
    Route::post('mesas/insertar', [MesaController::class, 'insertarMesa'])->name('mesas.insertar');
    Route::put('mesas/actualizar/{id}', [MesaController::class, 'actualizarMesa'])->name('mesas.actualizar');

    // Facturas (anulación de la venta: exige motivo)
    Route::patch('facturas/anular/{id}', [FacturaController::class, 'anularFactura'])->name('facturas.anular');

    // Resumen financiero en PDF (?desde=&hasta=)
    Route::get('reportes/financiero', [DocumentoController::class, 'reporteFinanciero'])->name('reportes.financiero');
});
