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
     data-url-facturas="{{ route('facturas') }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-kitchen-set me-2"></i>Órdenes de trabajo</h1>
            <p class="text-muted mb-0">Producción y entrega de los pedidos. Los programados aparecen primero. Se actualiza cada minuto.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="ordenes-refrescar">
            <i class="fa-solid fa-rotate me-1"></i>Actualizar
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <div class="filtro-rapido" id="ordenes-filtros" role="tablist" aria-label="Filtrar órdenes">
                <button type="button" class="btn is-activo" data-filtro-orden="activas">Por atender</button>
                <button type="button" class="btn" data-filtro-orden="entregada">Entregadas</button>
                <button type="button" class="btn" data-filtro-orden="cancelada">Canceladas</button>
                <button type="button" class="btn" data-filtro-orden="todas">Todas</button>
            </div>
        </div>
    </div>

    <div id="ordenes-contenido" aria-live="polite">
        <div class="orden-vacio"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ordenes.js') }}"></script>
@endpush
