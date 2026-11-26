@extends('layouts.app_layout')

@section('titulo', 'Ingresos | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/ingresos.css') }}">
@endpush

@section('contenido')
<div id="modulo-ingresos"
     data-url-todos="{{ route('ingresos.todos') }}"
     data-url-resumen="{{ route('ingresos.resumen') }}"
     data-url-insertar="{{ route('ingresos.insertar') }}"
     data-url-actualizar="{{ route('ingresos.actualizar', ['id' => '__ID__']) }}"
     data-url-anular="{{ route('ingresos.anular', ['id' => '__ID__']) }}"
     data-tipos="{{ json_encode($tipos) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Ingresos externos</h1>
            <p class="text-muted mb-0">Dinero que entra al negocio y no viene de las ventas: capital inicial, inversiones, aportes de socios, préstamos y donaciones. Suma al flujo de caja, no a la utilidad.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-nuevo-ingreso">
            <i class="fa-solid fa-plus me-1"></i>Registrar ingreso
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label for="ingresos-desde" class="form-label mb-1">Desde</label>
                    <input type="date" class="form-control" id="ingresos-desde">
                </div>
                <div class="col-6 col-md-3">
                    <label for="ingresos-hasta" class="form-label mb-1">Hasta</label>
                    <input type="date" class="form-control" id="ingresos-hasta">
                </div>
                <div class="col-8 col-md-4">
                    <label for="ingresos-tipo-filtro" class="form-label mb-1">Tipo</label>
                    <select class="form-select" id="ingresos-tipo-filtro"><option value="">Todos</option></select>
                </div>
                <div class="col-4 col-md-2 text-end">
                    <button type="button" class="btn btn-outline-secondary" id="ingresos-limpiar" title="Limpiar filtros"><i class="fa-solid fa-rotate-left"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Total ingresado</div><div class="dato-clave__valor" id="ingresos-total">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Ingresos</div><div class="dato-clave__valor" id="ingresos-cantidad">—</div></div></div>
        <div class="col-12 col-lg-6"><div class="dato-clave"><div class="dato-clave__titulo">Por tipo</div><div class="ingresos-por-tipo" id="ingresos-por-tipo"></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <input type="search" class="form-control" placeholder="Buscar en los resultados..." data-filtro="#tabla-ingresos" aria-label="Buscar">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-ingresos">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Monto</th>
                        <th>Registrado por</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-ingresos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Registrar ingreso</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-ingresos" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ingresos-tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" id="ingresos-tipo" name="tipo" required></select>
                            </div>
                            <div class="col-md-6">
                                <label for="ingresos-fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="ingresos-fecha" name="fecha_ingreso" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ingresos-monto" class="form-label">Monto ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="ingresos-monto" name="monto" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-12">
                                <label for="ingresos-descripcion" class="form-label">Descripción <span class="text-danger d-none" id="ingresos-descripcion-req">*</span></label>
                                <input type="text" class="form-control" id="ingresos-descripcion" name="descripcion" maxlength="255">
                                <div class="form-text">Obligatoria cuando el tipo es "Otros". Ej.: nombre del socio o de la entidad.</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-ingresos" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ingresos.js') }}"></script>
@endpush
