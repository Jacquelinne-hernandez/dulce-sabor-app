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
            ['Órdenes', 'Producción y entrega de los pedidos.', 'ordenes', 'fa-kitchen-set'],
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
            ['Gastos', 'Arrendamiento, luz, agua y otros gastos.', 'gastos', 'fa-file-invoice'],
            ['Ingresos', 'Capital, inversiones y otros aportes externos.', 'ingresos', 'fa-hand-holding-dollar'],
        ]];
        $secciones[] = ['Administración', 'Equipo y control', 'administracion', [
            ['Usuarios', 'Cuentas del personal del sistema.', 'usuarios', 'fa-user-gear'],
            ['Bitácora', 'Auditoría de las acciones del sistema.', 'bitacora', 'fa-clipboard-list'],
        ]];
    }
    if (!$esAdmin) {
        $secciones[0][3][] = ['Catálogo', 'Consultar precios y existencias.', 'productos.catalogo', 'fa-book-open'];
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
                {{-- Ícono creativo de batidor de repostería con corazoncitos y destellos --}}
                <svg class="home-batidor" viewBox="0 0 80 80" fill="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="batidorMango" x1="10" y1="8" x2="34" y2="38" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#f78fb3"/>
                            <stop offset="55%" stop-color="#d6336c"/>
                            <stop offset="100%" stop-color="#8f3cae"/>
                        </linearGradient>
                        <linearGradient id="batidorAlambre" x1="28" y1="30" x2="58" y2="66" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#ffe0ec"/>
                            <stop offset="100%" stop-color="#f78fb3"/>
                        </linearGradient>
                    </defs>
                    <!-- Aro sutil decorativo detrás -->
                    <circle cx="38" cy="42" r="26" fill="rgba(255, 224, 236, .45)"/>
                    <!-- Mango ergonómico del batidor -->
                    <path d="M13 11 C15 9, 19 10, 21 13 L33 31 C34 33, 33 35, 31 36 C29 37, 27 36, 26 34 L13 17 C11 14, 11 12, 13 11 Z"
                          fill="url(#batidorMango)" stroke="#a61e4d" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M16 14 L25 27" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" opacity=".8"/>
                    <!-- Alambres entrelazados del batidor -->
                    <path d="M31 35 C41 42, 57 56, 51 64 C45 71, 29 55, 31 35 Z"
                          fill="url(#batidorAlambre)" fill-opacity=".45" stroke="#d6336c" stroke-width="2.3"/>
                    <path d="M31 35 C46 39, 62 52, 55 60 C48 68, 33 51, 31 35 Z"
                          stroke="#c2255c" stroke-width="2"/>
                    <path d="M31 35 C36 48, 49 63, 42 67 C35 71, 27 52, 31 35 Z"
                          stroke="#c2255c" stroke-width="2"/>
                    <!-- Corazoncitos y destellos dulces flotando -->
                    <path d="M51 17c-1.7-2-4.6-1.9-6.2.1-1.6-2-4.5-2.1-6.2-.1-1.9 2.1-1.6 5.5.6 7.6l5.6 5.1 5.6-5.1c2.2-2.1 2.5-5.5.6-7.6z"
                          fill="#e0316b"/>
                    <path d="M66 28c-1.2-1.4-3.3-1.3-4.4.1-1.1-1.4-3.2-1.5-4.4-.1-1.3 1.5-1.1 3.9.4 5.4l4 3.7 4-3.7c1.5-1.5 1.7-3.9.4-5.4z"
                          fill="#f78fb3"/>
                    <path d="M39 28c-.8-.9-2.2-.8-2.9.1-.7-.9-2.1-1-2.9-.1-.9 1-.7 2.6.3 3.6l2.6 2.4 2.6-2.4c1-1 1.2-2.6.3-3.6z"
                          fill="#a94bc1"/>
                    <!-- Destellos tipo estrella -->
                    <path d="M61 12 L62.3 15.2 L65.5 16.5 L62.3 17.8 L61 21 L59.7 17.8 L56.5 16.5 L59.7 15.2 Z" fill="#d6336c"/>
                    <circle cx="69" cy="43" r="2.2" fill="#f78fb3"/>
                </svg>

                Bienvenido,&nbsp;<span class="home-nombre-wrap">{{ auth()->user()->nombre }}<svg class="home-gorro-chef" viewBox="0 0 110 92" fill="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="gorroCopa" x1="20" y1="10" x2="90" y2="62" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#fff0f5"/>
                                <stop offset="45%" stop-color="#ffc2d6"/>
                                <stop offset="100%" stop-color="#f47ea8"/>
                            </linearGradient>
                            <linearGradient id="gorroBanda" x1="26" y1="56" x2="84" y2="78" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#f78fb3"/>
                                <stop offset="100%" stop-color="#d6336c"/>
                            </linearGradient>
                        </defs>
                        <ellipse cx="55" cy="79" rx="28" ry="4" fill="rgba(214, 51, 108, .18)"/>
                        <path d="M27 54 C13 51, 10 31, 25 23 C28 9, 48 5, 58 14 C69 5, 89 10, 90 25 C103 33, 99 52, 83 54 L27 54 Z"
                              fill="url(#gorroCopa)" stroke="#d6336c" stroke-width="2.8" stroke-linejoin="round"/>
                        <path d="M24 32 C22 25, 29 19, 36 18" stroke="#ffffff" stroke-width="3" stroke-linecap="round" opacity=".9"/>
                        <path d="M50 14 C56 11, 64 12, 68 16" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" opacity=".85"/>
                        <path d="M36 28 C35 37, 36 44, 38 51" stroke="#e05688" stroke-width="2.2" stroke-linecap="round" opacity=".7"/>
                        <path d="M55 24 C55 34, 55 43, 55 51" stroke="#e05688" stroke-width="2.2" stroke-linecap="round" opacity=".7"/>
                        <path d="M74 28 C73 37, 72 44, 70 51" stroke="#e05688" stroke-width="2.2" stroke-linecap="round" opacity=".7"/>
                        <path d="M27 53 C45 50, 65 50, 83 53 L80 71 C63 67, 47 67, 30 71 Z"
                              fill="url(#gorroBanda)" stroke="#b82358" stroke-width="2.6" stroke-linejoin="round"/>
                        <path d="M29 63 C46 60, 64 60, 81 63" stroke="#ffd6e5" stroke-width="2" stroke-linecap="round"/>
                    </svg></span>
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
