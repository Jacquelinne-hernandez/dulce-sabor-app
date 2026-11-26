@extends('layouts.app_layout')

@section('titulo', 'Pedidos | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pedidos.css') }}">
@endpush
@section('contenido')
<div id="modulo-pedidos"
     data-url-todos="{{ route('pedidos.todos') }}"
     data-url-insertar="{{ route('pedidos.insertar') }}"
     data-url-buscar="{{ route('pedidos.buscar', ['id' => '__ID__']) }}"
     data-url-fecha="{{ route('pedidos.fecha') }}"
     data-url-estado="{{ route('pedidos.estado') }}"
     data-url-actualizar-estado="{{ route('pedidos.actualizarEstado', ['id' => '__ID__']) }}"
     data-productos="{{ $productos->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'precio' => $p->precio, 'stock' => $p->stock])->values()->toJson() }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-cart-shopping me-2"></i>Pedidos</h1>
            <p class="text-muted mb-0">Registro y seguimiento de pedidos, desde su preparación hasta la entrega.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo pedido
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-pedidos" aria-label="Buscar">
                </div>
                <div class="col-md-3 col-lg-2">
                    <input type="date" class="form-control" id="filtro-fecha-pedidos" aria-label="Filtrar por fecha">
                </div>
                <div class="col-md-4 col-lg-3">
                    <select class="form-select" id="filtro-estado-pedidos" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_preparacion">En preparación</option>
                        <option value="listo">Listo</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-secondary" id="limpiar-filtros-pedidos" title="Limpiar filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-pedidos">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Mesa</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-pedidos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nuevo pedido</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-pedidos" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pedidos-id_cliente" class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select class="form-select" id="pedidos-id_cliente" name="id_cliente" required>
                                    <option value="">Seleccione...</option>
                            @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="pedidos-id_mesa" class="form-label">Mesa</label>
                                <select class="form-select" id="pedidos-id_mesa" name="id_mesa">
                                    <option value="">Para llevar (sin mesa)</option>
                            @foreach($mesas as $mesa)
                                    <option value="{{ $mesa->id }}">Mesa {{ $mesa->numero }} (capacidad {{ $mesa->capacidad }}) · {{ ucfirst($mesa->estado) }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="pedidos-observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="pedidos-observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 class="h6 mb-0">Productos del pedido</h3>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-linea-pedidos">
                                        <i class="fa-solid fa-plus me-1"></i>Agregar producto
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th class="text-end">Precio</th>
                                                <th class="text-end">Subtotal</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="lineas-pedidos"></tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total</th>
                                                <th class="text-end" id="total-pedidos">$0.00</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="form-text">Solo se listan productos disponibles. Las existencias se descuentan al registrar el pedido.</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-pedidos" class="btn btn-primary">Registrar pedido</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-pedidos-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle del pedido</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detalle-pedidos"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pedidos.js') }}"></script>
@endpush
