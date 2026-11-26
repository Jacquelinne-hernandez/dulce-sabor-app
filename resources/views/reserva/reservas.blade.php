@extends('layouts.app_layout')

@section('titulo', 'Reservas | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/reservas.css') }}">
@endpush

@section('contenido')
<div id="modulo-reservas"
     data-url-todos="{{ route('reservas.todos') }}"
     data-url-buscar="{{ route('reservas.buscar', ['id' => '__ID__']) }}"
     data-url-insertar="{{ route('reservas.insertar') }}"
     data-url-mesas-disponibles="{{ route('reservas.mesasDisponibles') }}"
     data-url-cotizar="{{ route('reservas.cotizar') }}"
     data-url-clientes="{{ route('clientes.filtrar') }}"
     data-url-estado="{{ route('reservas.actualizarEstado', ['id' => '__ID__']) }}"
     data-url-pedidos="{{ route('pedidos') }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-calendar-check me-2"></i>Reservas</h1>
            <p class="text-muted mb-0">Reserve una o varias mesas para un cliente registrado. Se cobra la tarifa por hora de cada mesa.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-nueva-reserva">
            <i class="fa-solid fa-plus me-1"></i>Nueva reserva
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-lg-4">
                    <input type="search" class="form-control" id="reservas-buscar"
                           placeholder="Buscar cliente, código o mesa..." aria-label="Buscar">
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <select class="form-select" id="reservas-filtro-estado" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="atendida">Atendida</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <input type="date" class="form-control" id="reservas-filtro-fecha" aria-label="Filtrar por fecha">
                </div>
                <div class="col-12 col-lg-2 text-lg-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="reservas-limpiar">
                        <i class="fa-solid fa-rotate-left me-1"></i>Limpiar
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
                        <th>Mesas</th>
                        <th>Personas</th>
                        <th>Cobro mesas</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Nueva reserva --}}
    <div class="modal fade" id="modal-reservas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Nueva reserva</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-reservas" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                                <div id="reservas-cliente"></div>
                                <div class="form-text">Las reservas solo se hacen a clientes registrados.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="reservas-fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="reservas-fecha" name="fecha" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="reservas-hora-inicio" class="form-label">Desde <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="reservas-hora-inicio" name="hora_inicio" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="reservas-hora-fin" class="form-label">Hasta <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="reservas-hora-fin" name="hora_fin" required>
                            </div>
                            <div class="col-md-2">
                                <label for="reservas-personas" class="form-label">Personas <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="reservas-personas" name="cantidad_personas" min="1" step="1" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Mesas disponibles <span class="text-danger">*</span></label>
                                <div id="reservas-mesas" class="reserva-mesas">
                                    <div class="reserva-mesas__aviso">Indique la fecha y el horario para ver las mesas libres.</div>
                                </div>
                            </div>

                            <div class="col-12 d-none" id="reservas-cotizacion"></div>

                            <div class="col-12">
                                <label for="reservas-observaciones" class="form-label">Observaciones <span class="texto-pequeno">(opcional)</span></label>
                                <textarea class="form-control" id="reservas-observaciones" name="observaciones" rows="2" maxlength="500"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-reservas" class="btn btn-primary">Guardar reserva</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle --}}
    <div class="modal fade" id="modal-reservas-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle de la reserva</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="reservas-detalle-cuerpo"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reservas.js') }}"></script>
@endpush
