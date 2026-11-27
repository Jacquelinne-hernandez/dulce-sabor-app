@extends('layouts.app_layout')

@section('titulo', 'Proveedores | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/proveedores.css') }}">
@endpush
@section('contenido')
<div id="modulo-proveedores"
     data-url-todos="{{ route('proveedores.todos') }}"
     data-url-insertar="{{ route('proveedores.insertar') }}"
     data-url-buscar="{{ route('proveedores.buscar', ['id' => '__ID__']) }}"
     data-url-actualizar="{{ route('proveedores.actualizar', ['id' => '__ID__']) }}"
     data-url-eliminar="{{ route('proveedores.eliminar', ['id' => '__ID__']) }}"
     data-productos="{{ $productos->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre])->values()->toJson() }}"
     data-categorias="{{ $categorias->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values()->toJson() }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-truck me-2"></i>Proveedores</h1>
            <p class="text-muted mb-0">Empresas y personas que abastecen la pastelería.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo proveedor
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-proveedores" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-proveedores">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Productos</th>
                        <th>Dirección</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-proveedores" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nuevo proveedor</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-proveedores" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="proveedores-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="proveedores-nombre" name="nombre" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="proveedores-nombre_contacto" class="form-label">Persona de contacto</label>
                                <input type="text" class="form-control" id="proveedores-nombre_contacto" name="nombre_contacto" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="proveedores-telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="proveedores-telefono" name="telefono" maxlength="9" placeholder="0000-0000">
                            </div>
                            <div class="col-md-6">
                                <label for="proveedores-email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="proveedores-email" name="email" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label for="proveedores-direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="proveedores-direccion" name="direccion" maxlength="200">
                            </div>
                            <div class="col-12">
                                <label for="proveedores-descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="proveedores-descripcion" name="descripcion" rows="3" maxlength="250"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h3 class="h6 mb-0">Productos que ofrece</h3>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-producto-proveedor">
                                        <i class="fa-solid fa-plus me-1"></i>Agregar producto
                                    </button>
                                </div>
                                <div class="form-text mb-2">
                                    Solo se listan productos de tipo <strong>comprado</strong>. Elija uno existente
                                    o cree uno nuevo, con el precio al que este proveedor lo vende.
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th style="width:170px">Precio del proveedor ($)</th>
                                                <th style="width:50px"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores-lineas"></tbody>
                                    </table>
                                </div>
                                <div class="text-muted small mt-2" id="proveedores-sin-productos">
                                    Este proveedor aún no tiene productos asignados.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-proveedores" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/proveedores.js') }}"></script>
@endpush
