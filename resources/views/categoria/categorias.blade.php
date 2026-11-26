@extends('layouts.app_layout')

@section('titulo', 'Categorías | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/categorias.css') }}">
@endpush

@section('contenido')
<div id="modulo-categorias"
     data-url-todos="{{ route('categorias.todos') }}"
     data-url-insertar="{{ route('categorias.insertar') }}"
     data-url-buscar="{{ route('categorias.buscar', ['id' => '__ID__']) }}"
     data-url-actualizar="{{ route('categorias.actualizar', ['id' => '__ID__']) }}"
     data-url-eliminar="{{ route('categorias.eliminar', ['id' => '__ID__']) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-tags me-2"></i>Categorías</h1>
            <p class="text-muted mb-0">Clasificación de los productos de la pastelería.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nueva categoría
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-categorias" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-categorias">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-categorias" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nueva categoría</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-categorias" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="categorias-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="categorias-nombre" name="nombre" required maxlength="50">
                            </div>
                            <div class="col-12">
                                <label for="categorias-descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="categorias-descripcion" name="descripcion" rows="3" maxlength="150"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-categorias" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/categorias.js') }}"></script>
@endpush
