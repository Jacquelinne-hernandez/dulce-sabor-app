@extends('layouts.app_layout')

@section('titulo', 'Facturas | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/facturas.css') }}">
@endpush
@php
    $esAdmin = auth()->user()->rol?->nombre === 'Administrador';
@endphp

@section('contenido')
<div id="modulo-facturas"
     data-url-todos="{{ route('facturas.todos') }}"
     data-url-insertar="{{ route('facturas.insertar') }}"
     data-url-buscar="{{ route('facturas.buscar', ['id' => '__ID__']) }}"
     data-url-anular="{{ route('facturas.anular', ['id' => '__ID__']) }}"
     data-puede-editar="{{ $esAdmin ? 'true' : 'false' }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Facturas</h1>
            <p class="text-muted mb-0">Facturación de pedidos entregados. Cada factura suma puntos de fidelidad al cliente.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Emitir factura
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-facturas" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-facturas">
                <thead class="table-light">
                    <tr>
                        <th>N.º factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Total</th>
                        <th>Recibido</th>
                        <th>Cambio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-facturas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Emitir factura</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-facturas" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="facturas-id_pedido" class="form-label">Pedido entregado <span class="text-danger">*</span></label>
                                <select class="form-select" id="facturas-id_pedido" name="id_pedido" required>
                                    <option value="">Seleccione un pedido...</option>
                            @foreach($pedidos as $pedido)
                                    <option value="{{ $pedido->id }}" data-total="{{ $pedido->total }}">#{{ $pedido->id }} · {{ $pedido->cliente?->nombre }} · ${{ number_format($pedido->total, 2) }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="form-text">Total a pagar</div>
                                <div class="fs-4 fw-bold" id="factura-total">$0.00</div>
                            </div>
                            <div class="col-md-4">
                                <label for="facturas-efectivo_recibido" class="form-label">Efectivo recibido ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="facturas-efectivo_recibido" name="efectivo_recibido" required min="0.01" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <div class="form-text">Cambio</div>
                                <div class="fs-4 fw-bold" id="factura-cambio">$0.00</div>
                            </div>
                            <div class="col-12">
                                <div class="form-text">Solo se pueden facturar pedidos en estado "entregado" que aún no tengan factura. El pago se registra en efectivo.</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-facturas" class="btn btn-primary">Emitir factura</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-facturas-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle de la factura</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detalle-facturas"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/facturas.js') }}"></script>
@endpush
