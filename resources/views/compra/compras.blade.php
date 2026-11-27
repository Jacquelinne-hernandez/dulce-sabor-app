@extends('layouts.app_layout')

@section('titulo', 'Compras | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/compras.css') }}">
@endpush

@section('contenido')
<div id="modulo-compras"
     data-url-todos="{{ route('compras.todos') }}"
     data-url-insertar="{{ route('compras.insertar') }}"
     data-url-buscar="{{ route('compras.buscar', ['id' => '__ID__']) }}"
     data-url-abonos="{{ route('compras.abonos', ['id' => '__ID__']) }}"
     data-url-deudas="{{ route('compras.deudas') }}"
     data-productos="{{ $productos->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'costo' => $p->costo, 'stock' => $p->stock])->values()->toJson() }}"
     data-proveedores="{{ $proveedores->map(fn ($p) => [
         'id' => $p->id,
         'nombre' => $p->nombre,
         'productos' => $p->productos->map(fn ($x) => [
             'id' => $x->id,
             'nombre' => $x->nombre,
             'stock' => $x->stock,
             'costo' => $x->costo,
             'precio_compra' => $x->pivot->precio_compra,
         ])->values(),
     ])->values()->toJson() }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-basket-shopping me-2"></i>Compras</h1>
            <p class="text-muted mb-0">Compras a proveedores, al contado o a crédito. Cada compra incrementa el inventario y actualiza el costo.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-nueva-compra">
            <i class="fa-solid fa-plus me-1"></i>Registrar compra
        </button>
    </div>

    {{-- Deudas con proveedores --}}
    <div class="row g-3 mb-3" id="compras-deudas">
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Compras con saldo</div><div class="dato-clave__valor" id="deuda-pendientes">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Vencidas</div><div class="dato-clave__valor text-danger" id="deuda-vencidas">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Total adeudado</div><div class="dato-clave__valor" id="deuda-total">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Total vencido</div><div class="dato-clave__valor text-danger" id="deuda-total-vencido">—</div></div></div>
        <div class="col-12 d-none" id="deuda-proveedores-caja">
            <details class="deuda-proveedores">
                <summary>Ver deuda por proveedor</summary>
                <div class="table-responsive mt-2">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Proveedor</th><th class="text-end">Compras</th><th class="text-end">Saldo</th><th class="text-end">Vencido</th></tr></thead>
                        <tbody id="deuda-proveedores"></tbody>
                    </table>
                </div>
            </details>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-lg-4">
                    <input type="search" class="form-control" id="compras-buscar" placeholder="Buscar proveedor o número..." aria-label="Buscar">
                </div>
                <div class="col-lg-8">
                    <div class="filtro-rapido" id="compras-filtros">
                        <button type="button" class="btn btn-sm is-activo" data-filtro-pago="">Todas</button>
                        <button type="button" class="btn btn-sm" data-filtro-pago="saldo">Con saldo</button>
                        <button type="button" class="btn btn-sm" data-filtro-pago="vencida">Vencidas</button>
                        <button type="button" class="btn btn-sm" data-filtro-pago="pagada">Pagadas</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-compras">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Condición</th>
                        <th>Total</th>
                        <th>Saldo</th>
                        <th>Pago</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Registrar compra --}}
    <div class="modal fade" id="modal-compras" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Registrar compra</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-compras" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="compras-id_proveedor" class="form-label">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-select" id="compras-id_proveedor" name="id_proveedor" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Condición de pago <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-6 opcion-tarjeta">
                                        <input type="radio" name="condicion_pago" id="condicion-contado" value="contado" checked>
                                        <label for="condicion-contado"><i class="fa-solid fa-money-bill-wave"></i>Al contado</label>
                                    </div>
                                    <div class="col-6 opcion-tarjeta">
                                        <input type="radio" name="condicion_pago" id="condicion-credito" value="credito">
                                        <label for="condicion-credito"><i class="fa-solid fa-calendar-check"></i>A crédito</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5 d-none" data-solo-credito>
                                <label for="compras-vencimiento" class="form-label">Fecha de vencimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="compras-vencimiento" name="fecha_vencimiento">
                            </div>
                            <div class="col-md-4 d-none" data-solo-credito>
                                <label for="compras-abono" class="form-label">Abono inicial ($)</label>
                                <input type="number" class="form-control" id="compras-abono" name="abono_inicial" min="0" step="0.01" placeholder="0.00">
                                <div class="form-text">Opcional; debe ser menor al total.</div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 class="h6 mb-0">Productos comprados</h3>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-linea-compras">
                                        <i class="fa-solid fa-plus me-1"></i>Agregar producto
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th style="width:110px">Cantidad</th>
                                                <th style="width:150px">Costo unitario ($)</th>
                                                <th class="text-end">Subtotal</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="lineas-compras"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-md-6 offset-md-6">
                                <div class="caja-totales">
                                    <div class="caja-totales__fila caja-totales__fila--total"><span>Total de la compra</span><span class="texto-monto" id="total-compras">$0.00</span></div>
                                    <div class="caja-totales__fila d-none" data-solo-credito><span>Abono inicial</span><span class="texto-monto" id="resumen-abono">$0.00</span></div>
                                    <div class="caja-totales__fila d-none" data-solo-credito><span>Saldo a crédito</span><span class="texto-monto" id="resumen-saldo">$0.00</span></div>
                                </div>
                                <div class="form-text mt-1">Al registrar, las existencias aumentan y el costo se recalcula como promedio ponderado.</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-compras" class="btn btn-primary">Registrar compra</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle y abonos --}}
    <div class="modal fade" id="modal-compras-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle de la compra</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="detalle-compras"></div>
                    <form id="form-abono" class="mt-3 d-none" novalidate autocomplete="off">
                        <h3 class="h6">Registrar abono</h3>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-2">
                            <div class="col-sm-4">
                                <label for="abono-monto" class="form-label">Monto ($)</label>
                                <input type="number" class="form-control" id="abono-monto" name="monto" min="0.01" step="0.01">
                            </div>
                            <div class="col-sm-8">
                                <label for="abono-obs" class="form-label">Observaciones</label>
                                <input type="text" class="form-control" id="abono-obs" name="observaciones" maxlength="255">
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Registrar abono</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/compras.js') }}"></script>
@endpush
