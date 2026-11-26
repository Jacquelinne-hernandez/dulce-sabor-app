@extends('layouts.app_layout')

@section('titulo', 'Catálogo | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/catalogo.css') }}">
@endpush

@section('contenido')
<div id="modulo-catalogo" data-url-todos="{{ route('productos.catalogo.todos') }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-book-open me-2"></i>Catálogo</h1>
            <p class="text-muted mb-0">Precios y existencias de los productos. Solo consulta.</p>
        </div>
        <span class="badge text-bg-secondary fs-6" id="catalogo-total">0 productos</span>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" id="catalogo-buscar"
                           placeholder="Buscar producto o categoría..." aria-label="Buscar">
                </div>
                <div class="col-md-4 col-lg-3">
                    <select class="form-select" id="catalogo-disponibilidad" aria-label="Filtrar por disponibilidad">
                        <option value="">Todos</option>
                        <option value="disponible">Con existencias</option>
                        <option value="bajo">Stock bajo</option>
                        <option value="agotado">Agotados</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="cat-grid" id="catalogo-grid" aria-live="polite">
        <div class="cat-vacio"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/catalogo.js') }}"></script>
@endpush
