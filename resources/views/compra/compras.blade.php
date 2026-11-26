@extends('layouts.app_layout')

@section('titulo', 'Compras | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/compras.css') }}">
@endpush

@section('contenido')
<div id="modulo-compras"
     data-url-todos="{{ route('compras.todos') }}"
     data-url-insertar="{{ route('compras.insertar') }}"
     data-url-buscar="{{ route('compras.buscar', ['id' => '__ID__']) }}"
     data-productos="{{ $productos->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre])->values()->toJson() }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-basket-shopping me-2"></i>Compras</h1>
            <p class="text-muted mb-0">Compras a proveedores. Cada compra incrementa el inventario.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Registrar compra
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-compras" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-compras">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Registrada por</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-compras" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Registrar compra</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-compras" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="compras-id_proveedor" class="form-label">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-select" id="compras-id_proveedor" name="id_proveedor" required>
                                    <option value="">Seleccione...</option>
                            @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 class="h6 mb-0">Productos comprados</h3>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-linea-compras">
                                        <i class="fa-solid fa-plus me-1"></i>Agregar producto
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th>Precio unitario ($)</th>
                                                <th class="text-end">Subtotal</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="lineas-compras"></tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total</th>
                                                <th class="text-end" id="total-compras">$0.00</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="form-text">Al registrar la compra, las existencias de cada producto aumentan automáticamente.</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-compras" class="btn btn-primary">Registrar compra</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-compras-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle de la compra</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detalle-compras"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/compras.js') }}"></script>
@endpush
