@extends('layouts.app_layout')

@section('titulo', 'Dashboard | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@php
    $d = fn ($v) => ((float) $v < 0 ? '-$' : '$') . number_format(abs((float) $v), 2);
    $neg = fn ($v) => (float) $v < 0 ? 'text-danger' : '';
    $r = $datos['resultados'];
    $f = $datos['flujo_caja'];
    $c = $datos['compras'];
    $deu = $datos['deudas'];
    $op = $datos['operativo'];
    $desde = \Carbon\Carbon::parse($datos['desde'])->format('d/m/Y');
    $hasta = \Carbon\Carbon::parse($datos['hasta'])->format('d/m/Y');
    $nombresGasto = [
        'arrendamiento' => 'Arrendamiento', 'luz' => 'Luz', 'agua' => 'Agua', 'internet' => 'Internet',
        'salarios' => 'Salarios', 'mantenimiento' => 'Mantenimiento', 'otros' => 'Otros',
    ];
    $maxGasto = max(0.01, (float) max($datos['gastos_por_tipo']));

    // [título, valor, detalle, ícono, tono, clase del valor]
    $tarjetas = [
        ['Ventas', $d($r['ventas']), $r['facturas'] . ' facturas (IVA incl.)', 'fa-sack-dollar', 'success', ''],
        ['Utilidad bruta', $d($r['utilidad_bruta']), 'Ventas netas − costo de ventas', 'fa-chart-pie', 'info', $neg($r['utilidad_bruta'])],
        ['Gastos', $d($r['gastos']), 'Gastos adicionales', 'fa-file-invoice', 'warning', ''],
        ['Utilidad neta', $d($r['utilidad_neta']), 'Después de gastos', 'fa-scale-balanced', 'primary', $neg($r['utilidad_neta'])],
        ['Cobrado', $d($f['cobrado']), 'Efectivo + tarjeta', 'fa-cash-register', 'secondary', ''],
        ['Flujo de caja', $d($f['neto']), 'Cobrado − proveedores − gastos', 'fa-money-bill-transfer', 'dark', $neg($f['neto'])],
    ];
@endphp

@section('contenido')
<div id="modulo-dashboard">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</h1>
            <p class="text-muted mb-0">Resultados del negocio del {{ $desde }} al {{ $hasta }}.</p>
        </div>
        <a class="btn btn-primary" target="_blank" rel="noopener"
           href="{{ route('reportes.financiero', ['desde' => $datos['desde'], 'hasta' => $datos['hasta']]) }}">
            <i class="fa-solid fa-file-pdf me-1"></i>Reporte financiero (PDF)
        </a>
    </div>

    {{-- Filtro de fechas --}}
    <form method="GET" action="{{ route('dashboard') }}" class="dash-panel dash-filtro mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="dash-desde" class="form-label mb-1">Desde</label>
                <input type="date" class="form-control" id="dash-desde" name="desde" value="{{ $datos['desde'] }}" max="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="dash-hasta" class="form-label mb-1">Hasta</label>
                <input type="date" class="form-control" id="dash-hasta" name="hasta" value="{{ $datos['hasta'] }}" max="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-12 col-md-6 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i>Aplicar</button>
                <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Mes en curso</a>
                <span class="texto-pequeno align-self-center">Máximo un año por consulta.</span>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        @foreach($tarjetas as [$titulo, $valor, $detalle, $icono, $tono, $clase])
            <div class="col-6 col-lg-4 col-xl-2">
                <div class="dash-metrica dash-metrica--{{ $tono }} dash-metrica--compacta">
                    <div>
                        <div class="dash-metrica__titulo">{{ $titulo }}</div>
                        <div class="dash-metrica__valor {{ $clase }}">{{ $valor }}</div>
                        <div class="dash-metrica__sub">{{ $detalle }}</div>
                    </div>
                    <span class="ds-icono ds-tono--{{ $tono }}"><i class="fa-solid {{ $icono }}"></i></span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">

        {{-- Gráfica --}}
        <div class="col-12">
            <div class="dash-panel">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-chart-line me-1"></i>Ventas, compras y gastos por día</h2>
                </div>
                <div id="dash-grafica" data-serie="{{ json_encode($datos['serie_diaria']) }}"></div>
                <details class="dash-tabla-datos mt-3">
                    <summary>Ver los datos en tabla</summary>
                    <div class="table-responsive dash-tabla-datos__scroll">
                        <table class="table table-sm align-middle dash-tabla mb-0" id="dash-tabla-datos">
                            <thead><tr><th>Fecha</th><th class="text-end">Ventas</th><th class="text-end">Compras</th><th class="text-end">Gastos</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>

        {{-- Estado de resultados --}}
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-scale-balanced me-1"></i>Estado de resultados</h2>
                </div>
                <table class="table dash-resultados mb-0">
                    <tbody>
                        <tr><td>Ventas (IVA incluido)</td><td class="text-end">{{ $d($r['ventas']) }}</td></tr>
                        <tr class="dash-resultados__menor"><td>− IVA (13 %)</td><td class="text-end">{{ $d($r['iva']) }}</td></tr>
                        <tr><td>Ventas netas</td><td class="text-end">{{ $d($r['ventas_netas']) }}</td></tr>
                        <tr class="dash-resultados__menor"><td>− Costo de ventas</td><td class="text-end">{{ $d($r['costo_ventas']) }}</td></tr>
                        <tr class="dash-resultados__subtotal"><td>Utilidad bruta</td><td class="text-end {{ $neg($r['utilidad_bruta']) }}">{{ $d($r['utilidad_bruta']) }}</td></tr>
                        <tr class="dash-resultados__menor"><td>− Gastos</td><td class="text-end">{{ $d($r['gastos']) }}</td></tr>
                        <tr class="dash-resultados__total"><td>Utilidad neta</td><td class="text-end {{ $neg($r['utilidad_neta']) }}">{{ $d($r['utilidad_neta']) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Flujo de caja --}}
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-money-bill-transfer me-1"></i>Flujo de caja</h2>
                </div>
                <table class="table dash-resultados mb-3">
                    <tbody>
                        <tr><td>Cobrado en efectivo</td><td class="text-end">{{ $d($f['cobrado_efectivo']) }}</td></tr>
                        <tr><td>Cobrado con tarjeta</td><td class="text-end">{{ $d($f['cobrado_tarjeta']) }}</td></tr>
                        <tr class="dash-resultados__menor"><td>− Pagado a proveedores</td><td class="text-end">{{ $d($f['pagado_proveedores']) }}</td></tr>
                        <tr class="dash-resultados__menor"><td>− Gastos</td><td class="text-end">{{ $d($f['gastos']) }}</td></tr>
                        <tr class="dash-resultados__total"><td>Flujo neto</td><td class="text-end {{ $neg($f['neto']) }}">{{ $d($f['neto']) }}</td></tr>
                    </tbody>
                </table>
                <h3 class="dash-subtitulo">Compras del período</h3>
                <p class="mb-0 texto-pequeno">
                    {{ $c['cantidad'] }} compras por {{ $d($c['total_comprado']) }}, de las cuales {{ $d($c['a_credito']) }} fueron a crédito.
                </p>
            </div>
        </div>

        {{-- Gastos por tipo --}}
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-file-invoice me-1"></i>Gastos por tipo</h2>
                    <a href="{{ route('gastos') }}" class="btn btn-sm btn-outline-primary">Ver gastos</a>
                </div>
                @foreach($nombresGasto as $clave => $nombre)
                    <div class="dash-barra">
                        <span class="dash-barra__nombre">{{ $nombre }}</span>
                        <span class="dash-barra__pista"><span class="dash-barra__relleno" style="width: {{ round(((float) $datos['gastos_por_tipo'][$clave]) / $maxGasto * 100) }}%"></span></span>
                        <span class="dash-barra__valor">{{ $d($datos['gastos_por_tipo'][$clave]) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Deudas --}}
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Deudas con proveedores</h2>
                    <a href="{{ route('compras') }}" class="btn btn-sm btn-outline-primary">Ver compras</a>
                </div>
                <div class="dash-estados mb-3">
                    <div class="dash-estado dash-estado--pendiente"><strong>{{ $d($deu['total_adeudado']) }}</strong><span>Adeudado ({{ $deu['compras_pendientes'] }})</span></div>
                    <div class="dash-estado dash-estado--vencida"><strong>{{ $d($deu['total_vencido']) }}</strong><span>Vencido ({{ $deu['compras_vencidas'] }})</span></div>
                </div>
                <ul class="list-group list-group-flush dash-lista">
                    @forelse($deu['proximas'] as $p)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $p['proveedor'] }} · compra #{{ $p['id_compra'] }}</div>
                                <small class="dash-lista__sub">Vence {{ \Carbon\Carbon::parse($p['fecha_vencimiento'])->format('d/m/Y') }}</small>
                            </div>
                            <span class="badge ds-badge ds-tono--{{ $p['vencida'] ? 'danger' : 'warning' }}">{{ $d($p['saldo']) }}{{ $p['vencida'] ? ' · vencida' : '' }}</span>
                        </li>
                    @empty
                        <li class="list-group-item dash-vacio">No hay deudas pendientes.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Productos --}}
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-ranking-star me-1"></i>Más vendidos</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dash-tabla">
                        <thead><tr><th>Producto</th><th class="text-end">Unidades</th><th class="text-end">Ingresos</th></tr></thead>
                        <tbody>
                        @forelse($datos['mas_vendidos'] as $p)
                            <tr><td>{{ $p['producto'] }}</td><td class="text-end">{{ $p['unidades'] }}</td><td class="text-end">{{ $d($p['ingresos']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="dash-vacio">Sin ventas en el período.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-gem me-1"></i>Más rentables</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dash-tabla">
                        <thead><tr><th>Producto</th><th class="text-end">Unidades</th><th class="text-end">Utilidad</th></tr></thead>
                        <tbody>
                        @forelse($datos['mas_rentables'] as $p)
                            <tr><td>{{ $p['producto'] }}</td><td class="text-end">{{ $p['unidades'] }}</td><td class="text-end {{ $neg($p['utilidad']) }}">{{ $d($p['utilidad']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="dash-vacio">Sin ventas en el período.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Operación y stock --}}
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-kitchen-set me-1"></i>Operación ahora</h2>
                </div>
                <div class="dash-estados">
                    <div class="dash-estado dash-estado--pendiente"><strong>{{ $op['ordenes_pendientes'] }}</strong><span>Órdenes pendientes</span></div>
                    <div class="dash-estado dash-estado--produccion"><strong>{{ $op['ordenes_en_produccion'] }}</strong><span>En producción</span></div>
                    <div class="dash-estado dash-estado--lista"><strong>{{ $op['ordenes_listas'] }}</strong><span>Listas</span></div>
                    <div class="dash-estado"><strong>{{ $op['facturas_por_cobrar'] }}</strong><span>Por cobrar</span></div>
                    <div class="dash-estado"><strong>{{ $op['mesas_ocupadas'] }}</strong><span>Mesas ocupadas</span></div>
                    <div class="dash-estado"><strong>{{ $op['clientes_activos'] }}</strong><span>Clientes activos</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo"><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>Stock bajo</h2>
                    <span class="badge ds-badge ds-tono--warning">{{ count($datos['stock_bajo']) }}</span>
                </div>
                <ul class="list-group list-group-flush dash-lista">
                    @forelse(collect($datos['stock_bajo'])->take(8) as $s)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">{{ $s->producto }}</span>
                            <span class="badge ds-badge ds-tono--danger">{{ $s->stock }} / mín. {{ $s->stock_minimo }}</span>
                        </li>
                    @empty
                        <li class="list-group-item dash-vacio">Todo el inventario está al día.</li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
