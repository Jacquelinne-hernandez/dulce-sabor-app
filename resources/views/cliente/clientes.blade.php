@extends('layouts.app_layout')

@section('titulo', 'Clientes | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clientes.css') }}">
@endpush

@section('contenido')
<div id="modulo-clientes"
     data-url-todos="{{ route('clientes.todos') }}"
     data-url-insertar="{{ route('clientes.insertar') }}"
     data-url-buscar="{{ route('clientes.buscar', ['id' => '__ID__']) }}"
     data-url-actualizar="{{ route('clientes.actualizar', ['id' => '__ID__']) }}"
     data-url-eliminar="{{ route('clientes.eliminar', ['id' => '__ID__']) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-users me-2"></i>Clientes</h1>
            <p class="text-muted mb-0">Cartera de clientes de la pastelería y sus puntos de fidelidad.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo cliente
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-clientes" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-clientes">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Puntos</th>
                        <th>Registro</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-clientes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nuevo cliente</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-clientes" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="clientes-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="clientes-nombre" name="nombre" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="clientes-telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="clientes-telefono" name="telefono" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label for="clientes-correo" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="clientes-correo" name="correo" maxlength="100">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-clientes" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/clientes.js') }}"></script>
@endpush
