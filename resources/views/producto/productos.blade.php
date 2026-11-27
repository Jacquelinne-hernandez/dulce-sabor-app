@extends('layouts.app_layout')
@section('titulo', 'Productos | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/productos.css') }}">
@endpush

@section('contenido')
    <div id="modulo-productos"
         data-url-todos="{{ route('productos.todos') }}"
         data-url-buscar="{{ route('productos.buscar', ['id' => '__ID__']) }}"
         data-url-insertar="{{ route('productos.insertar') }}"
         data-url-actualizar="{{ route('productos.actualizar', ['id' => '__ID__']) }}"
         data-url-eliminar="{{ route('productos.eliminar', ['id' => '__ID__']) }}"
         data-url-reactivar="{{ route('productos.reactivar', ['id' => '__ID__']) }}"
         data-url-imagen="{{ route('productos.imagen.subir', ['id' => '__ID__']) }}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0"><i class="fa-solid fa-cake-candles me-2"></i>Productos</h1>
                <p class="text-muted mb-0">Catálogo de productos, precios y existencias.</p>
            </div>
            <button type="button" class="btn btn-primary" data-accion="nuevo">
                <i class="fa-solid fa-plus me-1"></i>Nuevo producto
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5 col-lg-4">
                        <input type="search" class="form-control" placeholder="Buscar producto..."
                               data-filtro="#tabla-productos" aria-label="Buscar">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="tabla-productos">
                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Origen</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="modal-productos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" data-titulo-modal>Nuevo producto</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form-productos" enctype="multipart/form-data" novalidate>
                            <div class="alert alert-danger d-none" role="alert" data-errores></div>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label for="productos-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="productos-nombre" name="nombre" maxlength="100" required>
                                </div>
                                <div class="col-md-5">
                                    <label for="productos-id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select" id="productos-id_categoria" name="id_categoria" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="productos-precio" class="form-label">Precio de venta ($) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="productos-precio" name="precio" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="productos-costo" class="form-label">Costo ($) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="productos-costo" name="costo" required>
                                    <div class="form-text">Las compras lo recalculan con el costo promedio ponderado.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="productos-stock" class="form-label">Stock <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="0" class="form-control" id="productos-stock" name="stock" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="productos-stock_minimo" class="form-label">Stock mínimo <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="0" class="form-control" id="productos-stock_minimo" name="stock_minimo" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="productos-origen" class="form-label">Origen <span class="text-danger">*</span></label>
                                    <select class="form-select" id="productos-origen" name="origen" required>
                                        <option value="produccion">Producción propia</option>
                                        <option value="compra">Comprado a proveedor</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="productos-descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="productos-descripcion" name="descripcion" rows="2" maxlength="255"></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="productos-imagen" class="form-label">Fotografía del producto (opcional)</label>
                                    <input type="file" class="form-control" id="productos-imagen" name="archivo_imagen" accept="image/png,image/jpeg,image/webp">
                                    <div class="form-text">JPG, PNG o WEBP, máximo 2 MB. Se guarda en el servidor con un código que identifica al producto.</div>
                                    <div class="mt-2 d-none" id="productos-preview-contenedor">
                                        <img id="productos-preview" src="" alt="Vista previa" style="max-height: 140px; border-radius: 16px; border: 2px solid #f7c1d8;">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="productos-quitar-imagen">
                                                <i class="fa-solid fa-trash me-1"></i>Quitar imagen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" form="form-productos" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/productos.js') }}"></script>
@endpush
