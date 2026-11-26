@extends('layouts.app_layout')
@section('titulo', 'Horarios | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/horarios.css') }}">
@endpush

@section('contenido')
    <div id="modulo-horarios"
         data-url-todos="{{ route('horarios.todos') }}"
         data-url-buscar="{{ route('horarios.buscar', ['id' => '__ID__']) }}"
         data-url-actualizar="{{ route('horarios.actualizar', ['id' => '__ID__']) }}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0"><i class="fa-solid fa-clock me-2"></i>Horarios</h1>
                <p class="text-muted mb-0">Horario de atención por día. Las reservas solo se aceptan dentro de este horario.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5 col-lg-4">
                        <input type="search" class="form-control" placeholder="Buscar día..."
                               data-filtro="#tabla-horarios" aria-label="Buscar">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="tabla-horarios">
                    <thead>
                    <tr>
                        <th>Día</th>
                        <th>Atiende</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="modal-horarios" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" data-titulo-modal>Editar horario</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form-horarios" novalidate>
                            <div class="alert alert-danger d-none" role="alert" data-errores></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="horarios-dia" class="form-label">Día</label>
                                    <input type="text" class="form-control" id="horarios-dia" readonly>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="horarios-activo" name="activo">
                                        <label class="form-check-label" for="horarios-activo">La pastelería atiende este día</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="horarios-hora_apertura" class="form-label">Hora de apertura</label>
                                    <input type="time" class="form-control" id="horarios-hora_apertura" name="hora_apertura">
                                </div>
                                <div class="col-md-6">
                                    <label for="horarios-hora_cierre" class="form-label">Hora de cierre</label>
                                    <input type="time" class="form-control" id="horarios-hora_cierre" name="hora_cierre">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" form="form-horarios" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/horarios.js') }}"></script>
@endpush
