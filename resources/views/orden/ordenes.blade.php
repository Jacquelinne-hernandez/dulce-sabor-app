@extends('layouts.app_layout')

@section('titulo', 'Órdenes | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/ordenes.css') }}">
@endpush

@section('contenido')
<div id="modulo-ordenes"
     data-url-activas="{{ route('ordenes.activas') }}"
     data-url-todos="{{ route('ordenes.todos') }}"
     data-url-estado-lista="{{ route('ordenes.estado') }}"
     data-url-actualizar="{{ route('ordenes.actualizarEstado', ['id' => '__ID__']) }}"
     data-url-pedido-estado="{{ route('pedidos.actualizarEstado', ['id' => '__ID__']) }}"
     data-url-facturas="{{ route('facturas') }}"
     data-horas-anticipacion="{{ \App\Models\Orden::HORAS_ANTICIPACION_PRODUCCION }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-kitchen-set me-2"></i>Órdenes de trabajo</h1>
            <p class="text-muted mb-0">Producción y entrega de los pedidos. Los programados aparecen primero. Se actualiza cada minuto.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="ordenes-refrescar">
            <i class="fa-solid fa-rotate me-1"></i>Actualizar
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-lg-7">
                    <div class="filtro-rapido" id="ordenes-filtros" role="tablist" aria-label="Filtrar órdenes">
                        <button type="button" class="btn is-activo" data-filtro-orden="activas">Por atender</button>
                        <button type="button" class="btn" data-filtro-orden="entregada">Entregadas</button>
                        <button type="button" class="btn" data-filtro-orden="cancelada">Canceladas</button>
                        <button type="button" class="btn" data-filtro-orden="todas">Todas</button>
                    </div>
                </div>
                <div class="col-lg-5">
                    <input type="search" class="form-control" id="ordenes-buscar" data-filtro="#tabla-ordenes"
                           placeholder="Buscar orden, cliente o producto..." aria-label="Buscar">
                </div>
            </div>
            <div class="filtro-rapido mt-2 d-none" id="ordenes-subfiltros" aria-label="Estado de la orden"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-ordenes">
                <thead class="table-light">
                    <tr>
                        <th>Orden</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Entrega</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Detalle --}}
    <div class="modal fade" id="modal-ordenes-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle de la orden</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="ordenes-detalle-cuerpo"></div>
                <div class="modal-footer">
                    <span class="me-auto" id="ordenes-detalle-acciones"></span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancelación --}}
    <div class="modal fade" id="modal-ordenes-cancelar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Cancelar orden y pedido</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-ordenes-cancelar" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <p class="mb-2" id="ordenes-cancelar-texto"></p>
                        <p class="mb-3 texto-pequeno">Se devolverán las existencias al inventario y se liberarán las mesas. Solo es posible antes de facturar.</p>
                        <label for="ordenes-motivo" class="form-label">Motivo <span class="texto-pequeno">(opcional)</span></label>
                        <textarea class="form-control" id="ordenes-motivo" name="motivo" rows="2" maxlength="200"></textarea>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" form="form-ordenes-cancelar" class="btn btn-danger">Cancelar orden</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ordenes.js') }}"></script>
@endpush
