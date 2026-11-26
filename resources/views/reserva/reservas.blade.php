@extends('layouts.app_layout')

@section('titulo', 'Reservas | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/reservas.css') }}">
@endpush
@section('contenido')
<div id="modulo-reservas"
     data-url-todos="{{ route('reservas.todos') }}"
     data-url-insertar="{{ route('reservas.insertar') }}"
     data-url-fecha="{{ route('reservas.fecha') }}"
     data-url-estado="{{ route('reservas.estado') }}"
     data-url-actualizar-estado="{{ route('reservas.actualizarEstado', ['id' => '__ID__']) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-calendar-check me-2"></i>Reservas</h1>
            <p class="text-muted mb-0">Reservas de mesas. Solo se aceptan dentro del horario de atención y sin traslapes.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nueva reserva
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-reservas" aria-label="Buscar">
                </div>
                <div class="col-md-3 col-lg-2">
                    <input type="date" class="form-control" id="filtro-fecha-reservas" aria-label="Filtrar por fecha">
                </div>
                <div class="col-md-4 col-lg-3">
                    <select class="form-select" id="filtro-estado-reservas" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="atendida">Atendida</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-secondary" id="limpiar-filtros-reservas" title="Limpiar filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-reservas">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Cliente</th>
                        <th>Mesa</th>
                        <th>Personas</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-reservas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nueva reserva</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-reservas" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reservas-id_cliente" class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select class="form-select" id="reservas-id_cliente" name="id_cliente" required>
                                    <option value="">Seleccione...</option>
                            @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="reservas-id_mesa" class="form-label">Mesa <span class="text-danger">*</span></label>
                                <select class="form-select" id="reservas-id_mesa" name="id_mesa" required>
                                    <option value="">Seleccione...</option>
                            @foreach($mesas as $mesa)
                                    <option value="{{ $mesa->id }}">Mesa {{ $mesa->numero }} (capacidad {{ $mesa->capacidad }})</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="reservas-fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="reservas-fecha" name="fecha" required>
                            </div>
                            <div class="col-md-4">
                                <label for="reservas-hora_inicio" class="form-label">Hora de inicio <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="reservas-hora_inicio" name="hora_inicio" required>
                            </div>
                            <div class="col-md-4">
                                <label for="reservas-hora_fin" class="form-label">Hora de fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="reservas-hora_fin" name="hora_fin" required>
                            </div>
                            <div class="col-md-6">
                                <label for="reservas-cantidad_personas" class="form-label">Cantidad de personas <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="reservas-cantidad_personas" name="cantidad_personas" required min="1" step="1">
                            </div>
                            <div class="col-12">
                                <label for="reservas-observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="reservas-observaciones" name="observaciones" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-reservas" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reservas.js') }}"></script>
@endpush
