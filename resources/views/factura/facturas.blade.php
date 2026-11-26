@extends('layouts.app_layout')

@section('titulo', 'Facturas | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/facturas.css') }}">
@endpush

@php
    $esAdmin = auth()->user()->rol?->nombre === 'Administrador';
@endphp

@section('contenido')
<div id="modulo-facturas"
     data-url-todos="{{ route('facturas.todos') }}"
     data-url-buscar="{{ route('facturas.buscar', ['id' => '__ID__']) }}"
     data-url-por-facturar="{{ route('facturas.porFacturar') }}"
     data-url-cotizar="{{ route('facturas.cotizar') }}"
     data-url-insertar="{{ route('facturas.insertar') }}"
     data-url-pago="{{ route('pagos.insertar') }}"
     data-url-anular="{{ route('facturas.anular', ['id' => '__ID__']) }}"
     data-url-pdf="{{ route('facturas.pdf', ['id' => '__ID__']) }}"
     data-url-ticket="{{ route('facturas.ticket', ['id' => '__ID__']) }}"
     data-es-admin="{{ $esAdmin ? 'true' : 'false' }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Facturas</h1>
            <p class="text-muted mb-0">Se factura un pedido cuando su orden ya fue entregada. El IVA (13 %) está incluido en los precios.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-nueva-factura">
            <i class="fa-solid fa-plus me-1"></i>Nueva factura
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="search" class="form-control" id="facturas-buscar"
                           placeholder="Buscar factura, cliente o pedido..." aria-label="Buscar">
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select" id="facturas-filtro-estado" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="emitida">Por cobrar</option>
                        <option value="pagada">Pagadas</option>
                        <option value="anulada">Anuladas</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" class="form-control" id="facturas-filtro-fecha" aria-label="Filtrar por fecha">
                </div>
                <div class="col-12 col-md-1 text-md-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="facturas-limpiar" title="Limpiar filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-facturas">
                <thead class="table-light">
                    <tr>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Total</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Nueva factura --}}
    <div class="modal fade" id="modal-facturas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Nueva factura</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-facturas" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>

                        <div class="mb-3">
                            <label for="facturas-pedido" class="form-label">Pedido a facturar <span class="text-danger">*</span></label>
                            <select class="form-select" id="facturas-pedido" name="id_pedido" required>
                                <option value="">Seleccione...</option>
                            </select>
                            <div class="form-text" id="facturas-pedido-ayuda">Solo aparecen pedidos abiertos con la orden entregada y sin factura.</div>
                        </div>

                        <div class="row g-3 d-none" id="facturas-bloque-puntos">
                            <div class="col-md-7">
                                <label for="facturas-puntos" class="form-label">Puntos a canjear</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="facturas-puntos" name="puntos_canjeados" min="0" step="1" value="0">
                                    <button type="button" class="btn btn-outline-secondary" id="facturas-puntos-max">Usar el máximo</button>
                                </div>
                                <div class="form-text" id="facturas-puntos-ayuda"></div>
                            </div>
                        </div>

                        <div class="mt-3 d-none" id="facturas-cotizacion"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-facturas" class="btn btn-primary" id="facturas-emitir" disabled>Emitir factura</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cobro --}}
    <div class="modal fade" id="modal-facturas-pago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Registrar pago</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-pago" novalidate autocomplete="off">
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>

                        <div class="caja-totales mb-3" id="pago-resumen"></div>

                        <label class="form-label">Método de pago</label>
                        <div class="row g-2 mb-3">
                            <div class="col-6 opcion-tarjeta">
                                <input type="radio" name="metodo" id="metodo-efectivo" value="efectivo" checked>
                                <label for="metodo-efectivo"><i class="fa-solid fa-money-bill-wave"></i>Efectivo</label>
                            </div>
                            <div class="col-6 opcion-tarjeta">
                                <input type="radio" name="metodo" id="metodo-tarjeta" value="tarjeta">
                                <label for="metodo-tarjeta"><i class="fa-solid fa-credit-card"></i>Tarjeta</label>
                            </div>
                        </div>

                        <p class="form-text mb-3">
                            <i class="fa-solid fa-circle-info me-1"></i>El pago cubre el saldo completo de la factura.
                        </p>

                        <div id="pago-efectivo">
                            <label for="pago-recibido" class="form-label">Efectivo recibido ($)</label>
                            <input type="number" class="form-control" id="pago-recibido" name="efectivo_recibido" min="0.01" step="0.01">
                            <div class="pago-cambio mt-2">Cambio a entregar: <strong id="pago-cambio">$0.00</strong></div>
                        </div>

                        <div id="pago-tarjeta" class="d-none">
                            <div class="alert alert-info py-2 mb-3">
                                <i class="fa-solid fa-circle-info me-1"></i>Pago simulado. Solo se guarda la marca y los últimos 4 dígitos.
                                Tarjeta de prueba: 4111 1111 1111 1111.
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="pago-titular" class="form-label">Titular</label>
                                    <input type="text" class="form-control" id="pago-titular" name="tarjeta_titular" maxlength="60">
                                </div>
                                <div class="col-12">
                                    <label for="pago-numero" class="form-label">Número de tarjeta</label>
                                    <input type="text" class="form-control" id="pago-numero" name="tarjeta_numero" inputmode="numeric"
                                           data-mascara="tarjeta" placeholder="0000 0000 0000 0000" maxlength="23">
                                </div>
                                <div class="col-6">
                                    <label for="pago-vence" class="form-label">Vencimiento</label>
                                    <input type="text" class="form-control" id="pago-vence" name="tarjeta_vencimiento" inputmode="numeric"
                                           data-mascara="vencimiento" placeholder="MM/AA" maxlength="5">
                                </div>
                                <div class="col-6">
                                    <label for="pago-cvv" class="form-label">CVV</label>
                                    <input type="password" class="form-control" id="pago-cvv" name="tarjeta_cvv" inputmode="numeric"
                                           data-mascara="digitos" placeholder="123" maxlength="4">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-pago" class="btn btn-primary">Registrar pago</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle --}}
    <div class="modal fade" id="modal-facturas-detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Detalle de la factura</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="facturas-detalle-cuerpo"></div>
                <div class="modal-footer" id="facturas-detalle-pie">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Anulación --}}
    <div class="modal fade" id="modal-facturas-anular" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Anular factura</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-anular" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <p class="mb-3">Se anula la venta completa: se revierten los pagos y los puntos, se cancela el pedido y vuelven las existencias. No se puede deshacer.</p>
                        <label for="anular-motivo" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="anular-motivo" name="motivo" rows="2" minlength="5" maxlength="200" required></textarea>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" form="form-anular" class="btn btn-danger">Anular factura</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/facturas.js') }}"></script>
@endpush
