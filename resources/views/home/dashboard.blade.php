@extends('layouts.app_layout')

@section('titulo', 'Dashboard | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@php
    $esAdmin = auth()->user()->rol?->nombre === 'Administrador';

    $colorEstado = [
        'pendiente' => 'warning',
        'en_preparacion' => 'info',
        'listo' => 'primary',
        'entregado' => 'success',
        'cancelado' => 'danger',
    ];

    // [título, valor, ícono, color]
    $tarjetas = [
        ['Pedidos de hoy', $resumen['pedidos_hoy'], 'fa-cart-shopping', 'primary'],
        ['Pedidos en curso', $resumen['pedidos_en_curso'], 'fa-fire-burner', 'warning'],
        ['Ventas de hoy', '$' . number_format($resumen['ventas_hoy'], 2), 'fa-sack-dollar', 'success'],
        ['Reservas de hoy', $resumen['reservas_hoy'], 'fa-calendar-check', 'info'],
        ['Mesas disponibles', $resumen['mesas_disponibles'], 'fa-chair', 'secondary'],
        ['Clientes activos', $resumen['clientes_activos'], 'fa-users', 'dark'],
    ];
@endphp

@section('contenido')

    <div class="mb-4">
        <h1 class="ds-pagina-titulo"><i class="fa-solid fa-chart-line"></i>Dashboard</h1>
        <p class="ds-pagina-sub">Resumen de la operación al {{ now()->format('d/m/Y') }}.</p>
    </div>

    {{-- Indicadores --}}
    <div class="row g-3 mb-4">
        @foreach($tarjetas as [$titulo, $valor, $icono, $color])
            <div class="col-6 col-lg-4 col-xl-2">
                <div class="dash-metrica">
                    <div>
                        <div class="dash-metrica__titulo">{{ $titulo }}</div>
                        <div class="dash-metrica__valor">{{ $valor }}</div>
                    </div>
                    <span class="ds-icono ds-tono--{{ $color }}"><i class="fa-solid {{ $icono }}"></i></span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">

        {{-- Últimos pedidos --}}
        <div class="col-lg-7">
            <div class="dash-panel h-100">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo">Últimos pedidos</h2>
                    <a href="{{ route('pedidos') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dash-tabla">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Mesa</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ultimosPedidos as $pedido)
                            <tr>
                                <td>{{ $pedido->id }}</td>
                                <td>{{ $pedido->cliente?->nombre }}</td>
                                <td>{{ $pedido->mesa ? 'Mesa ' . $pedido->mesa->numero : 'Para llevar' }}</td>
                                <td class="text-end">${{ number_format($pedido->total, 2) }}</td>
                                <td>
                                        <span class="badge ds-badge ds-tono--{{ $colorEstado[$pedido->estado] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $pedido->estado)) }}
                                        </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="dash-vacio">Aún no hay pedidos registrados.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">

            {{-- Stock bajo --}}
            <div class="dash-panel mb-3">
                <div class="dash-panel__cabecera">
                    <h2 class="dash-panel__titulo">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>Productos con stock bajo
                    </h2>
                    <span class="badge ds-badge ds-tono--warning">{{ $productosStockBajo->count() }}</span>
                </div>
                <ul class="list-group list-group-flush dash-lista">
                    @forelse($productosStockBajo->take(8) as $producto)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $producto->nombre }}</div>
                                <small class="dash-lista__sub">{{ $producto->categoria?->nombre }}</small>
                            </div>
                            <span class="badge ds-badge ds-tono--danger">
                                {{ $producto->stock }} / mín. {{ $producto->stock_minimo }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item dash-vacio">Todo el inventario está al día.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Usuarios por estado (solo administrador) --}}
            @if($esAdmin)
                <div class="dash-panel">
                    <div class="dash-panel__cabecera">
                        <h2 class="dash-panel__titulo">Usuarios por estado</h2>
                    </div>
                    <ul class="list-group list-group-flush dash-lista">
                        @foreach($usuariosPorEstado as $fila)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $fila['estado'] }}
                                <span class="badge ds-badge ds-tono--primary">{{ $fila['total'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>
    </div>

@endsection
