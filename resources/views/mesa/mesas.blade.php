@extends('layouts.app_layout')

@section('titulo', 'Mesas | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/mesas.css') }}">
@endpush

@php
    $esAdmin = auth()->user()->rol?->nombre === 'Administrador';
@endphp

@section('contenido')
    <div id="modulo-mesas"
         data-url-todos="{{ route('mesas.todos') }}"
         data-url-insertar="{{ route('mesas.insertar') }}"
         data-url-buscar="{{ route('mesas.buscar', ['id' => '__ID__']) }}"
         data-url-actualizar="{{ route('mesas.actualizar', ['id' => '__ID__']) }}"
         data-url-estado="{{ route('mesas.estado', ['estado' => '__ESTADO__']) }}"
         data-url-cambiar-estado="{{ route('mesas.cambiarEstado', ['id' => '__ID__']) }}"
         data-puede-editar="{{ $esAdmin ? 'true' : 'false' }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0"><i class="fa-solid fa-chair me-2"></i>Mesas</h1>
                <p class="text-muted mb-0">Estado de las mesas del local. El estado también cambia automáticamente con los pedidos.</p>
            </div>
            @if($esAdmin)
                <button type="button" class="btn btn-primary" data-accion="nuevo">
                    <i class="fa-solid fa-plus me-1"></i>Nueva mesa
                </button>
            @endif
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5 col-lg-4">
                        <input type="search" class="form-control" placeholder="Buscar..."
                               data-filtro="#tabla-mesas" aria-label="Buscar">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <select class="form-select" id="filtro-estado-mesas" aria-label="Filtrar por estado">
                            <option value="">Todos los estados</option>
                            <option value="disponible">Disponible</option>
                            <option value="reservada">Reservada</option>
                            <option value="ocupada">Ocupada</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabla-mesas">
                    <thead class="table-light">
                    <tr>
                        <th>Mesa</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th>Cambiar estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        @if($esAdmin)
            <div class="modal fade" id="modal-mesas" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title h5" data-titulo-modal>Nueva mesa</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form id="form-mesas" novalidate>
                                <div class="alert alert-danger d-none" role="alert" data-errores></div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="mesas-numero" class="form-label">Número de mesa <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="mesas-numero" name="numero" required min="1" step="1">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mesas-capacidad" class="form-label">Capacidad (personas) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="mesas-capacidad" name="capacidad" required min="1" step="1">
                                    </div>
                                    <div class="col-12" id="grupo-mesas-estado">
                                        <label for="mesas-estado" class="form-label">Estado de la mesa</label>
                                        <select class="form-select" id="mesas-estado" name="estado">
                                            <option value="disponible">Disponible</option>
                                            <option value="reservada">Reservada</option>
                                            <option value="ocupada">Ocupada</option>
                                            <option value="mantenimiento">Mantenimiento</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" form="form-mesas" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/mesas.js') }}"></script>
@endpush
