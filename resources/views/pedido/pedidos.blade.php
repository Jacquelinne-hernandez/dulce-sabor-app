@extends('layouts.app_layout')

@section('titulo', 'Pedidos | Dulce Sabor Sys')
@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pedidos.css') }}">
@endpush

@section('contenido')
    <div id="modulo-pedidos"
         data-url-todos="{{ route('pedidos.todos') }}"
         data-url-buscar="{{ route('pedidos.buscar', ['id' => '__ID__']) }}"
         data-url-insertar="{{ route('pedidos.insertar') }}"
         data-url-estado="{{ route('pedidos.actualizarEstado', ['id' => '__ID__']) }}"
         data-url-reservas="{{ route('pedidos.reservasPorAtender') }}"
         data-url-clientes="{{ route('clientes.filtrar') }}"
         data-url-catalogo="{{ route('productos.catalogo.todos') }}"
         data-url-mesas="{{ route('mesas.todos') }}"
         data-url-facturas="{{ route('facturas') }}"
         data-url-ordenes="{{ route('ordenes') }}"
         data-puntos-unidad="{{ \App\Models\Factura::PUNTOS_POR_UNIDAD }}"
         data-minutos-anticipacion="{{ \App\Models\Pedido::MINUTOS_ANTICIPACION }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0"><i class="fa-solid fa-cart-shopping me-2"></i>Pedidos</h1>
                <p class="text-muted mb-0">Registre pedidos en el local, para llevar o de una reserva. Cada pedido genera su orden de trabajo.</p>
            </div>
            <button type="button" class="btn btn-primary" id="btn-nuevo-pedido">
                <i class="fa-solid fa-plus me-1"></i>Nuevo pedido
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="search" class="form-control" id="pedidos-buscar"
                               placeholder="Buscar cliente, orden o número..." aria-label="Buscar">
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select" id="pedidos-filtro-estado" aria-label="Filtrar por estado">
                            <option value="">Todos los estados</option>
                            <option value="abierto">Abiertos</option>
                            <option value="completado">Completados</option>
                            <option value="cancelado">Cancelados</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select" id="pedidos-filtro-tipo" aria-label="Filtrar por tipo">
                            <option value="">Todos los tipos</option>
                            <option value="local">En el local</option>
                            <option value="llevar">Para llevar</option>
                            <option value="reserva">De reserva</option>
                        </select>
                    </div>
                    <div class="col-8 col-md-3">
                        <input type="date" class="form-control" id="pedidos-filtro-fecha" aria-label="Filtrar por fecha">
                    </div>
                    <div class="col-4 col-md-1 text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="pedidos-limpiar" title="Limpiar filtros">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabla-pedidos">
                    <thead class="table-light">
                    <tr>
                        <th>N.º</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Mesa / Entrega</th>
                        <th>Orden</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        {{-- Nuevo pedido --}}
        <div class="modal fade" id="modal-pedidos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5">Nuevo pedido</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form-pedidos" novalidate>
                            <div class="alert alert-danger d-none" role="alert" data-errores></div>

                            <div class="row g-4 align-items-stretch">
                                {{-- Columna izquierda: Datos del pedido y resumen --}}
                                <div class="col-lg-5">
                                    <div class="pedido-panel-bloque h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="pedido-subtitulo-seccion">
                                                <i class="fa-solid fa-clipboard-list"></i>
                                                <span>Datos de la orden</span>
                                            </div>

                                            <label class="form-label">Tipo de pedido <span class="text-danger">*</span></label>
                                            <div class="row g-2 mb-3">
                                                <div class="col-4 opcion-tarjeta">
                                                    <input type="radio" name="tipo" id="tipo-local" value="local" checked>
                                                    <label for="tipo-local"><i class="fa-solid fa-utensils"></i>En el local</label>
                                                </div>
                                                <div class="col-4 opcion-tarjeta">
                                                    <input type="radio" name="tipo" id="tipo-llevar" value="llevar">
                                                    <label for="tipo-llevar"><i class="fa-solid fa-bag-shopping"></i>Llevar</label>
                                                </div>
                                                <div class="col-4 opcion-tarjeta">
                                                    <input type="radio" name="tipo" id="tipo-reserva" value="reserva">
                                                    <label for="tipo-reserva"><i class="fa-solid fa-calendar-check"></i>Reserva</label>
                                                </div>
                                            </div>

                                            {{-- En el local --}}
                                            <div class="mb-3" data-tipo="local">
                                                <label for="pedidos-mesa" class="form-label">Mesa <span class="text-danger">*</span></label>
                                                <select class="form-select" id="pedidos-mesa" name="id_mesa">
                                                    <option value="">Seleccione...</option>
                                                </select>
                                                <div class="form-text">Una mesa ocupada solo se puede elegir si tiene un pedido abierto del local.</div>
                                            </div>

                                            {{-- Reserva --}}
                                            <div class="mb-3 d-none" data-tipo="reserva">
                                                <label for="pedidos-reserva" class="form-label">Reserva de hoy <span class="text-danger">*</span></label>
                                                <select class="form-select" id="pedidos-reserva" name="id_reserva">
                                                    <option value="">Seleccione...</option>
                                                </select>
                                                <div class="form-text" id="pedidos-reserva-ayuda">Solo aparecen reservas confirmadas de hoy que aún no tienen pedido.</div>
                                            </div>

                                            {{-- Cliente (no aplica a reservas: lo trae la reserva) --}}
                                            <div class="mb-3" data-tipo="local llevar">
                                                <label class="form-label">Cliente <span class="texto-pequeno">(opcional)</span></label>
                                                <div id="pedidos-cliente"></div>
                                            </div>

                                            {{-- Para llevar --}}
                                            <div class="mb-3 d-none" data-tipo="llevar">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="pedidos-programar">
                                                    <label class="form-check-label" for="pedidos-programar">Programar para otra fecha y hora</label>
                                                </div>
                                                <div class="d-none" id="pedidos-bloque-programado">
                                                    <input type="datetime-local" class="form-control" id="pedidos-fecha-programada" name="fecha_programada">
                                                    <div class="form-text">Con al menos <span id="pedidos-anticipacion"></span> minutos de anticipación y dentro del horario de atención.</div>
                                                </div>
                                            </div>

                                            <div class="pedido-check-caja mb-3" data-tipo="local llevar" id="pedidos-bloque-inmediata">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" id="pedidos-inmediata" name="entrega_inmediata">
                                                    <label class="form-check-label" for="pedidos-inmediata">
                                                        <strong>Entrega inmediata</strong>
                                                        <span class="d-block texto-pequeno">Venta de mostrador: la orden nace entregada.</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="pedidos-observaciones" class="form-label">Observaciones <span class="texto-pequeno">(opcional)</span></label>
                                                <textarea class="form-control" id="pedidos-observaciones" name="observaciones" rows="2" maxlength="500" placeholder="Notas especiales para cocina o empaque..."></textarea>
                                            </div>
                                        </div>

                                        <div class="caja-totales mt-2" id="pedidos-totales"></div>
                                    </div>
                                </div>

                                {{-- Columna derecha: Vitrina de productos y pedido actual --}}
                                <div class="col-lg-7">
                                    <div class="pedido-panel-bloque h-100 d-flex flex-column">
                                        <div class="pedido-subtitulo-seccion">
                                            <i class="fa-solid fa-cake-candles"></i>
                                            <span>Catálogo disponible <span class="text-danger">*</span></span>
                                        </div>

                                        <input type="search" class="form-control mb-3" id="pedidos-buscar-producto"
                                               placeholder="Buscar pastel, postre o bebida..." aria-label="Buscar producto">

                                        <div class="pedido-catalogo" id="pedidos-catalogo"></div>

                                        <div class="pedido-carrito-wrap mt-3 pt-3">
                                            <div class="pedido-subtitulo-seccion mb-2">
                                                <i class="fa-solid fa-basket-shopping"></i>
                                                <span>Pedido actual</span>
                                            </div>
                                            <div id="pedidos-carrito" class="pedido-carrito"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" form="form-pedidos" class="btn btn-primary">
                            <i class="fa-solid fa-check me-1"></i>Registrar pedido
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalle --}}
        <div class="modal fade" id="modal-pedidos-detalle" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5">Detalle del pedido</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body" id="pedidos-detalle-cuerpo"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cancelación --}}
        <div class="modal fade" id="modal-pedidos-cancelar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5">Cancelar pedido</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form-pedidos-cancelar" novalidate>
                            <div class="alert alert-danger d-none" role="alert" data-errores></div>
                            <p class="mb-3">Se devolverán las existencias y se liberarán las mesas. Esta acción no se puede deshacer.</p>
                            <label for="pedidos-motivo" class="form-label">Motivo <span class="texto-pequeno">(opcional)</span></label>
                            <textarea class="form-control" id="pedidos-motivo" name="motivo" rows="2" maxlength="200"></textarea>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                        <button type="submit" form="form-pedidos-cancelar" class="btn btn-danger">Cancelar pedido</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pedidos.js') }}"></script>
@endpush
