@extends('layouts.app_layout')

@section('titulo', 'Caja | Dulce Sabor Sys')

@section('contenido')
<div id="modulo-caja"
     data-url-movimientos="{{ route('caja.movimientos') }}"
     data-url-ingresos="{{ route('ingresos') }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-wallet me-2"></i>Caja</h1>
            <p class="text-muted mb-0">Dinero disponible de la empresa y su estado de cuenta. Las compras, los gastos y los pagos a proveedores solo se permiten si hay fondos.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('ingresos') }}">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i>Registrar ingreso
        </a>
    </div>

    <div class="alert alert-warning d-none" role="alert" id="caja-aviso">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        La empresa aún no tiene <strong>capital inicial</strong>. Regístrelo en
        <a class="alert-link" href="{{ route('ingresos') }}">Ingresos</a> (tipo «Capital inicial»)
        para poder comprar, pagar a proveedores y registrar gastos.
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="dato-clave caja-disponible">
                <div class="dato-clave__titulo">Dinero disponible</div>
                <div class="dato-clave__valor" id="caja-disponible">—</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="dato-clave"><div class="dato-clave__titulo">Capital inicial</div><div class="dato-clave__valor" id="caja-capital">—</div></div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="dato-clave"><div class="dato-clave__titulo">Por pagar a proveedores</div><div class="dato-clave__valor" id="caja-por-pagar">—</div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Ingresos externos</div><div class="dato-clave__valor" id="caja-ingresos">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Cobros de ventas</div><div class="dato-clave__valor" id="caja-cobros">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Pagado a proveedores</div><div class="dato-clave__valor" id="caja-pagos">—</div></div></div>
        <div class="col-6 col-lg-3"><div class="dato-clave"><div class="dato-clave__titulo">Gastos</div><div class="dato-clave__valor" id="caja-gastos">—</div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>Estado de cuenta</strong>
            <span class="text-muted small">Del movimiento más reciente al más antiguo. Los movimientos anulados no se muestran.</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-caja" data-sin-paginar>
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Detalle</th>
                        <th class="text-end">Entrada</th>
                        <th class="text-end">Salida</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
