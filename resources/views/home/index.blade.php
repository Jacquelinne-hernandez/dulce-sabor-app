@extends('layouts.app_layout')
@section('titulo', 'Inicio | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@php
    $esAdmin = auth()->user()->rol?->nombre === 'Administrador';
    $secciones = [
        ['Operación', 'Del pedido a la mesa', 'operacion', [
            ['Dashboard', 'Resumen de la operación del día.', 'dashboard', 'fa-chart-line'],
            ['Pedidos', 'Registrar y dar seguimiento a los pedidos.', 'pedidos', 'fa-cart-shopping'],
            ['Facturas', 'Emitir facturas de pedidos entregados.', 'facturas', 'fa-file-invoice-dollar'],
            ['Reservas', 'Reservar mesas para los clientes.', 'reservas', 'fa-calendar-check'],
            ['Mesas', 'Consultar y cambiar el estado de las mesas.', 'mesas', 'fa-chair'],
            ['Clientes', 'Administrar la cartera de clientes.', 'clientes', 'fa-users'],
        ]],
    ];
    if ($esAdmin) {
        $secciones[] = ['Inventario', 'Ingredientes y creaciones', 'inventario', [
            ['Productos', 'Catálogo, precios y existencias.', 'productos', 'fa-cake-candles'],
            ['Compras', 'Registrar compras a proveedores.', 'compras', 'fa-basket-shopping'],
        ]];
        $secciones[] = ['Administración', 'Equipo y control', 'administracion', [
            ['Usuarios', 'Cuentas del personal del sistema.', 'usuarios', 'fa-user-gear'],
            ['Bitácora', 'Auditoría de las acciones del sistema.', 'bitacora', 'fa-clipboard-list'],
        ]];
    }
@endphp

@section('contenido')
    {{-- Bienvenida --}}
    <section class="home-hero">
        <div class="home-hero__contenido">
            <span class="home-hero__fecha">
                {{ ucfirst(now()->locale('es')->translatedFormat('l, j \d\e F')) }}
            </span>
            <h1 class="home-titulo">
                Bienvenido, {{ auth()->user()->nombre }}
                <i class="fa-regular fa-heart home-titulo__adorno" aria-hidden="true"></i>
            </h1>
            <p class="home-subtitulo">
                Sesión iniciada como <strong>{{ auth()->user()->rol?->nombre }}</strong>.
                Seleccione un módulo para comenzar.
            </p>
        </div>
    </section>

    {{-- Módulos por sección --}}
    @foreach($secciones as [$nombreSeccion, $lema, $clave, $accesos])
        <section class="home-seccion home-seccion--{{ $clave }}">
            <div class="home-seccion__encabezado">
                <h2 class="home-seccion__titulo">{{ $nombreSeccion }}</h2>
                <span class="home-seccion__lema">{{ $lema }}</span>
            </div>
            <div class="row g-3">
                @foreach($accesos as [$titulo, $descripcion, $ruta, $icono])
                    <div class="col-sm-6 col-xl-4">
                        <a href="{{ route($ruta) }}" class="home-acceso home-acceso--{{ $ruta }}">
                            <span class="home-acceso__icono"><i class="fa-solid {{ $icono }} fa-fw"></i></span>
                            <div>
                                <h3 class="home-acceso__titulo">{{ $titulo }}</h3>
                                <p class="home-acceso__texto">{{ $descripcion }}</p>
                            </div>
                            <i class="fa-solid fa-arrow-right home-acceso__flecha" aria-hidden="true"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
