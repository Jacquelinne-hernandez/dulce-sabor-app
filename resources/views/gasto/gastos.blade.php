@extends('layouts.app_layout')

@section('titulo', 'Gastos | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/gastos.css') }}">
@endpush

@section('contenido')
<div id="modulo-gastos"
     data-url-todos="{{ route('gastos.todos') }}"
     data-url-resumen="{{ route('gastos.resumen') }}"
     data-url-insertar="{{ route('gastos.insertar') }}"
     data-url-actualizar="{{ route('gastos.actualizar', ['id' => '__ID__']) }}"
     data-url-anular="{{ route('gastos.anular', ['id' => '__ID__']) }}"
     data-tipos="{{ json_encode($tipos) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-file-invoice me-2"></i>Gastos</h1>
            <p class="text-muted mb-0">Gastos adicionales del negocio: arrendamiento, servicios, salarios y más. Se descuentan en el estado de resultados.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-nuevo-gasto">
            <i class="fa-solid fa-plus me-1"></i>Registrar gasto
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label for="gastos-desde" class="form-label mb-1">Desde</label>
                    <input type="date" class="form-control" id="gastos-desde">
                </div>
                <div class="col-6 col-md-3">
                    <label for="gastos-hasta" class="form-label mb-1">Hasta</label>
                    <input type="date" class="form-control" id="gastos-hasta">
                </div>
                <div class="col-8 col-md-4">
                    <label for="gastos-tipo-filtro" class="form-label mb-1">Tipo</label>
                    <select class="form-select" id="gastos-tipo-filtro"><option value="">Todos</option></select>
                </div>
                <div class="col-4 col-md-2 text-end">
                    <button type="button" class="btn btn-outline-secondary" id="gastos-limpiar" title="Limpiar filtros"><i class="fa-solid fa-rotate-left"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Total registrado</div><div class="dato-clave__valor" id="gastos-total">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Gastos</div><div class="dato-clave__valor" id="gastos-cantidad">—</div></div></div>
        <div class="col-12 col-lg-6"><div class="dato-clave"><div class="dato-clave__titulo">Por tipo</div><div class="gastos-por-tipo" id="gastos-por-tipo"></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-gastos">
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

    <div class="modal fade" id="modal-gastos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Registrar gasto</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-gastos" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="gastos-tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" id="gastos-tipo" name="tipo" required></select>
                            </div>
                            <div class="col-md-6">
                                <label for="gastos-fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="gastos-fecha" name="fecha_gasto" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gastos-monto" class="form-label">Monto ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="gastos-monto" name="monto" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-12">
                                <label for="gastos-descripcion" class="form-label">Descripción <span class="text-danger d-none" id="gastos-descripcion-req">*</span></label>
                                <input type="text" class="form-control" id="gastos-descripcion" name="descripcion" maxlength="255">
                                <div class="form-text">Obligatoria cuando el tipo es "Otros".</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-gastos" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/gastos.js') }}"></script>
@endpush
