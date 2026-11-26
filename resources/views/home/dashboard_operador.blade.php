@extends('layouts.app_layout')

@section('titulo', 'Dashboard | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@php
    $d = fn ($v) => ((float) $v < 0 ? '-$' : '$') . number_format(abs((float) $v), 2);
    $op = $datos['operativo'];
    $ordenesActivas = $op['ordenes_pendientes'] + $op['ordenes_en_produccion'] + $op['ordenes_listas'];
    $tipos = ['local' => 'En el local', 'llevar' => 'Para llevar', 'reserva' => 'De reserva'];

    // [título, valor, detalle, ícono, tono, enlace]
    $tarjetas = [
        ['Ventas de hoy', $d($datos['ventas']['total']), $datos['ventas']['facturas'] . ' facturas', 'fa-sack-dollar', 'success', route('facturas')],
        ['Cobrado hoy', $d($datos['cobros']['total']), 'Efectivo ' . $d($datos['cobros']['efectivo']) . ' · Tarjeta ' . $d($datos['cobros']['tarjeta']), 'fa-cash-register', 'info', route('facturas')],
        ['Pedidos de hoy', $datos['pedidos']['total'], 'Registrados en el día', 'fa-cart-shopping', 'primary', route('pedidos')],
        ['Por cobrar', $op['facturas_por_cobrar'], 'Facturas con saldo', 'fa-file-invoice-dollar', 'warning', route('facturas')],
        ['Órdenes activas', $ordenesActivas, 'Pendientes, en producción y listas', 'fa-kitchen-set', 'secondary', route('ordenes')],
        ['Mesas disponibles', $op['mesas_disponibles'], $op['mesas_ocupadas'] . ' ocupadas', 'fa-chair', 'dark', route('mesas')],
    ];
@endphp

@section('contenido')
<div id="modulo-dashboard">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</h1>
            <p class="text-muted mb-0">Resumen de la operación de hoy, {{ \Carbon\Carbon::parse($datos['fecha'])->format('d/m/Y') }}.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('pedidos') }}"><i class="fa-solid fa-plus me-1"></i>Nuevo pedido</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach($tarjetas as [$titulo, $valor, $detalle, $icono, $tono, $enlace])
            <div class="col-6 col-lg-4 col-xl-2">
                <a class="dash-metrica dash-metrica--{{ $tono }} dash-metrica--enlace" href="{{ $enlace }}">
                    <div>
                        <div class="dash-metrica__titulo">{{ $titulo }}</div>
                        <div class="dash-metrica__valor">{{ $valor }}</div>
                        <div class="dash-metrica__sub">{{ $detalle }}</div>
                    </div>
                    <span class="ds-icono ds-tono--{{ $tono }}"><i class="fa-solid {{ $icono }}"></i></span>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3">

        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-kitchen-set me-1"></i>Cocina y pedidos</h2>
                    <a href="{{ route('ordenes') }}" class="btn btn-sm btn-outline-primary">Ver órdenes</a>
                </div>
                <div class="dash-estados mb-3">
                    <div class="dash-estado dash-estado--pendiente"><strong>{{ $op['ordenes_pendientes'] }}</strong><span>Pendientes</span></div>
                    <div class="dash-estado dash-estado--produccion"><strong>{{ $op['ordenes_en_produccion'] }}</strong><span>En producción</span></div>
                    <div class="dash-estado dash-estado--lista"><strong>{{ $op['ordenes_listas'] }}</strong><span>Listas</span></div>
                </div>
                <h3 class="dash-subtitulo">Pedidos de hoy por tipo</h3>
                <ul class="list-group list-group-flush dash-lista">
                    @foreach($tipos as $clave => $texto)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $texto }}
                            <span class="badge ds-badge ds-tono--primary">{{ $datos['pedidos']['por_tipo'][$clave] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-ranking-star me-1"></i>Más vendidos hoy</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dash-tabla">
                        <thead><tr><th>Producto</th><th class="text-end">Unidades</th><th class="text-end">Ingresos</th></tr></thead>
                        <tbody>
                        @forelse($datos['mas_vendidos'] as $p)
                            <tr>
                                <td>{{ $p->producto }}</td>
                                <td class="text-end">{{ $p->unidades }}</td>
                                <td class="text-end">{{ $d($p->ingresos) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="dash-vacio">Todavía no hay ventas hoy.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-calendar-check me-1"></i>Reservas de hoy</h2>
                    <a href="{{ route('reservas') }}" class="btn btn-sm btn-outline-primary">Ver reservas</a>
                </div>
                <ul class="list-group list-group-flush dash-lista">
                    @forelse($datos['reservas_hoy'] as $r)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ substr($r->hora_inicio, 0, 5) }} – {{ substr($r->hora_fin, 0, 5) }} · {{ $r->cliente }}</div>
                                <small class="dash-lista__sub">Mesa(s) {{ $r->mesas }} · {{ $r->personas }} personas</small>
                            </div>
                            <span class="badge ds-badge ds-tono--{{ $r->estado === 'confirmada' ? 'primary' : 'warning' }}">{{ ucfirst($r->estado) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item dash-vacio">No hay reservas pendientes para hoy.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-clock me-1"></i>Pedidos programados</h2>
                </div>
                <ul class="list-group list-group-flush dash-lista">
                    @forelse($datos['programados'] as $p)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $p->fecha_programada->format('d/m/Y H:i') }} · {{ $p->cliente }}</div>
                                <small class="dash-lista__sub">Pedido #{{ $p->id_pedido }} · {{ $p->numero_orden }} · {{ $d($p->total) }}</small>
                            </div>
                            <span class="badge ds-badge ds-tono--info">{{ ucfirst(str_replace('_', ' ', $p->estado_orden)) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item dash-vacio">No hay pedidos programados.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-12">
            <div class="dash-panel">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>Stock bajo</h2>
                    <span class="badge ds-badge ds-tono--warning">{{ count($datos['stock_bajo']) }}</span>
                </div>
                <div class="row g-2">
                    @forelse($datos['stock_bajo'] as $s)
                        <div class="col-sm-6 col-lg-4">
                            <div class="dash-stock">
                                <span class="fw-semibold">{{ $s->producto }}</span>
                                <span class="badge ds-badge ds-tono--danger">{{ $s->stock }} / mín. {{ $s->stock_minimo }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 dash-vacio">Todo el inventario está al día.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
