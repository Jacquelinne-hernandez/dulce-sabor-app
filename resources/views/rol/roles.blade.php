@extends('layouts.app_layout')

@section('titulo', 'Roles | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/roles.css') }}">
@endpush

@section('contenido')
<div id="modulo-roles"
     data-url-todos="{{ route('roles.todos') }}"
     data-url-insertar="{{ route('roles.insertar') }}"
     data-url-buscar="{{ route('roles.buscar', ['id' => '__ID__']) }}"
     data-url-actualizar="{{ route('roles.actualizar', ['id' => '__ID__']) }}"
     data-url-eliminar="{{ route('roles.eliminar', ['id' => '__ID__']) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-user-shield me-2"></i>Roles</h1>
            <p class="text-muted mb-0">Perfiles de acceso del sistema. Los roles base no pueden renombrarse ni eliminarse.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo rol
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-roles" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-roles">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-roles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nuevo rol</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-roles" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="roles-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="roles-nombre" name="nombre" required maxlength="30">
                            </div>
                            <div class="col-12">
                                <label for="roles-descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="roles-descripcion" name="descripcion" rows="3" maxlength="150"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-roles" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/roles.js') }}"></script>
@endpush
