@php
    $usuarioActual = auth()->user();
    $esAdmin = $usuarioActual->rol?->nombre === 'Administrador';
    $activo = fn (string ...$rutas) => request()->routeIs(...$rutas) ? 'active' : '';
    $iniciales = mb_strtoupper(mb_substr($usuarioActual->nombre ?? '', 0, 1) . mb_substr($usuarioActual->apellido ?? '', 0, 1));
    $pendientes = (int) ($pedidosPendientes ?? 0);
@endphp
    <!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Dulce Sabor Sys')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/formato.css') }}">
    <link rel="stylesheet" href="{{ asset('css/paginacion.css') }}">
    @stack('estilos')
</head>
<body class="app-body">
<!-- Barra superior -->
<header class="navbar navbar-dark sticky-top p-0 app-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-lg-none me-2" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#menuLateral"
                    aria-controls="menuLateral" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand app-brand" href="{{ route('home') }}">
                <img class="app-brand__logo"
                     src="{{ asset('images/logo.svg') }}"
                     onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22 fill=%22none%22 stroke=%22%23ffffff%22 stroke-width=%223%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpath d=%22M14 34c-6-5-1-13 8-12 1-10 14-11 19-3 10-7 23 2 19 12 9 5 4 16-5 17H23c-9-1-13-6-9-14z%22/%3E%3Cpath d=%22M21 48l6 11h16l6-11M28 50l2 8M35 50v8M42 50l-2 8%22/%3E%3Ccircle cx=%2236%22 cy=%2213%22 r=%223%22 fill=%22%23ffffff%22/%3E%3C/svg%3E';"
                     alt="Dulce Sabor Sys">
                <span class="app-brand__texto">Dulce Sabor Sys</span>
            </a>
        </div>

        <div class="app-header__derecha">
            <a class="app-campana" href="{{ route('ordenes') }}" aria-label="Órdenes por atender">
                <i class="fa-regular fa-bell"></i>
                @if($pendientes > 0)
                    <span class="app-campana__cuenta">{{ $pendientes }}</span>
                @endif
            </a>
            <span class="app-header__separador d-none d-sm-block"></span>
            <div class="dropdown">
                <button class="btn app-user-button dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="app-avatar">{{ $iniciales }}</span>
                    <span class="app-user-button__rol">{{ $usuarioActual->rol?->nombre }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end app-dropdown">
                    <li class="app-dropdown__nombre">{{ $usuarioActual->nombre }} {{ $usuarioActual->apellido }}</li>
                    <li class="app-dropdown__correo">{{ $usuarioActual->email }}</li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid app-container">
    <div class="row app-row">
        <!-- Menú lateral -->
        <aside class="col-lg-2 p-0 app-sidebar">
            <div class="offcanvas-lg offcanvas-start app-sidebar-panel" tabindex="-1"
                 id="menuLateral" aria-labelledby="menuLateralTitulo">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="menuLateralTitulo">Menú</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            data-bs-target="#menuLateral" aria-label="Cerrar"></button>
                </div>
                <div class="offcanvas-body d-lg-flex flex-column p-2 pt-lg-3">
                    <ul class="app-nav">
                        <li>
                            <a class="app-nav-link {{ $activo('home') }}" href="{{ route('home') }}">
                                <i class="fa-solid fa-house fa-fw me-2"></i>Inicio
                            </a>
                        </li>
                        <li>
                            <a class="app-nav-link {{ $activo('dashboard') }}" href="{{ route('dashboard') }}">
                                <i class="fa-solid fa-chart-line fa-fw me-2"></i>Dashboard
                            </a>
                        </li>
                    </ul>

                    <h6 class="app-nav-heading">Operación</h6>
                    <ul class="app-nav">
                        <li>
                            <a class="app-nav-link {{ $activo('pedidos', 'pedidos.*') }}" href="{{ route('pedidos') }}">
                                <i class="fa-solid fa-cart-shopping fa-fw me-2"></i>Pedidos
                            </a>
                        </li>
                        <li>
                            <a class="app-nav-link {{ $activo('ordenes', 'ordenes.*') }}" href="{{ route('ordenes') }}">
                                <i class="fa-solid fa-kitchen-set fa-fw me-2"></i>Órdenes
                                @if($pendientes > 0)
                                    <span class="app-nav-link__cuenta">{{ $pendientes }}</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a class="app-nav-link {{ $activo('facturas', 'facturas.*') }}" href="{{ route('facturas') }}">
                                <i class="fa-solid fa-file-invoice-dollar fa-fw me-2"></i>Facturas
                            </a>
                        </li>
                        <li>
                            <a class="app-nav-link {{ $activo('reservas', 'reservas.*') }}" href="{{ route('reservas') }}">
                                <i class="fa-solid fa-calendar-check fa-fw me-2"></i>Reservas
                            </a>
                        </li>
                        <li>
                            <a class="app-nav-link {{ $activo('mesas', 'mesas.*') }}" href="{{ route('mesas') }}">
                                <i class="fa-solid fa-chair fa-fw me-2"></i>Mesas
                            </a>
                        </li>
                        <li>
                            <a class="app-nav-link {{ $activo('clientes', 'clientes.*') }}" href="{{ route('clientes') }}">
                                <i class="fa-solid fa-users fa-fw me-2"></i>Clientes
                            </a>
                        </li>
                        @unless($esAdmin)
                            <li>
                                <a class="app-nav-link {{ $activo('productos.catalogo') }}" href="{{ route('productos.catalogo') }}">
                                    <i class="fa-solid fa-book-open fa-fw me-2"></i>Catálogo
                                </a>
                            </li>
                        @endunless
                    </ul>

                    @if($esAdmin)
                        <h6 class="app-nav-heading">Inventario</h6>
                        <ul class="app-nav">
                            <li>
                                <a class="app-nav-link {{ $activo('productos', 'productos.*') }}" href="{{ route('productos') }}">
                                    <i class="fa-solid fa-cake-candles fa-fw me-2"></i>Productos
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('categorias', 'categorias.*') }}" href="{{ route('categorias') }}">
                                    <i class="fa-solid fa-tags fa-fw me-2"></i>Categorías
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('compras', 'compras.*') }}" href="{{ route('compras') }}">
                                    <i class="fa-solid fa-basket-shopping fa-fw me-2"></i>Compras
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('gastos', 'gastos.*') }}" href="{{ route('gastos') }}">
                                    <i class="fa-solid fa-file-invoice fa-fw me-2"></i>Gastos
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('ingresos', 'ingresos.*') }}" href="{{ route('ingresos') }}">
                                    <i class="fa-solid fa-hand-holding-dollar fa-fw me-2"></i>Ingresos
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('proveedores', 'proveedores.*') }}" href="{{ route('proveedores') }}">
                                    <i class="fa-solid fa-truck fa-fw me-2"></i>Proveedores
                                </a>
                            </li>
                        </ul>

                        <h6 class="app-nav-heading">Administración</h6>
                        <ul class="app-nav">
                            <li>
                                <a class="app-nav-link {{ $activo('usuarios', 'usuarios.*') }}" href="{{ route('usuarios') }}">
                                    <i class="fa-solid fa-user-gear fa-fw me-2"></i>Usuarios
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('roles', 'roles.*') }}" href="{{ route('roles') }}">
                                    <i class="fa-solid fa-user-shield fa-fw me-2"></i>Roles
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('estados', 'estados.*') }}" href="{{ route('estados') }}">
                                    <i class="fa-solid fa-toggle-on fa-fw me-2"></i>Estados
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('horarios', 'horarios.*') }}" href="{{ route('horarios') }}">
                                    <i class="fa-solid fa-clock fa-fw me-2"></i>Horarios
                                </a>
                            </li>
                            <li>
                                <a class="app-nav-link {{ $activo('bitacora') }}" href="{{ route('bitacora') }}">
                                    <i class="fa-solid fa-clipboard-list fa-fw me-2"></i>Bitácora
                                </a>
                            </li>
                        </ul>
                    @endif

                    <!-- Decoración del menú -->
                    <div class="app-sidebar-rama" aria-hidden="true"></div>
                    <div class="app-sidebar-pie">
                        <span class="app-sidebar-pie__lema">Endulzamos cada momento</span>
                        <i class="fa-regular fa-heart app-sidebar-pie__corazon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Contenido principal -->
        <main class="col-lg-10 px-md-4 py-4 app-main">
            @yield('contenido')
        </main>
    </div>
</div>

<!-- Confirmación reutilizable (la usa App.confirmar de global.js) -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" data-confirmar-titulo>Confirmar acción</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-confirmar-mensaje></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-confirmar-aceptar>Aceptar</button>
            </div>
        </div>
    </div>
</div>

@include('components.toast')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/global.js') }}"></script>
<script src="{{ asset('js/extras.js') }}"></script>
<script src="{{ asset('js/formato.js') }}"></script>
<script src="{{ asset('js/paginacion.js') }}"></script>
@stack('scripts')
</body>
</html>
