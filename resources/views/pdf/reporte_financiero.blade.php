@php
    $d = fn ($valor) => '$' . number_format((float) $valor, 2);
    $f = fn ($fecha) => \Carbon\Carbon::parse($fecha)->format('d/m/Y');
    $r = $datos['resultados'];
    $c = $datos['compras'];
    $caja = $datos['flujo_caja'];
    $deudas = $datos['deudas'];

    $nombresGasto = [
        'arrendamiento' => 'Arrendamiento',
        'luz' => 'Luz',
        'agua' => 'Agua',
        'internet' => 'Internet',
        'salarios' => 'Salarios',
        'mantenimiento' => 'Mantenimiento',
        'otros' => 'Otros',
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen financiero</title>
    <style>
        @page { margin: 36px 42px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 17px; margin: 0; }
        h2 { font-size: 12px; margin: 16px 0 4px 0; padding-bottom: 3px; border-bottom: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 5px; }
        .derecha { text-align: right; }
        .gris { color: #666; }
        .logo { height: 50px; }
        .tabla td, .tabla th { border-bottom: 1px solid #ddd; }
        .tabla th { text-align: left; font-size: 10px; border-bottom: 1px solid #333; }
        .sangria td:first-child { padding-left: 18px; }
        .subtotal td { border-top: 1px solid #333; font-weight: bold; }
        .vencida { font-weight: bold; }
        .pie { margin-top: 22px; font-size: 9px; text-align: center; color: #666; }
    </style>
</head>
<body>

<table>
    <tr>
        <td style="width: 55%; vertical-align: top;">
            @if($logo)
                <img class="logo" src="{{ $logo }}" alt="Dulce Sabor">
            @else
                <h1>Dulce Sabor</h1>
            @endif
        </td>
        <td class="derecha" style="vertical-align: top;">
            <h1>Resumen financiero</h1>
            <div>Del {{ $f($datos['desde']) }} al {{ $f($datos['hasta']) }}</div>
            <div class="gris">Generado el {{ $datos['generado'] }} por {{ $usuario->nombre }} {{ $usuario->apellido }}</div>
        </td>
    </tr>
</table>

<h2>Estado de resultados</h2>
<table class="tabla">
    <tr><td>Ventas facturadas (IVA incluido) · {{ $r['facturas'] }} facturas</td><td class="derecha">{{ $d($r['ventas']) }}</td></tr>
    <tr class="sangria"><td class="gris">(-) IVA contenido en las ventas</td><td class="derecha gris">{{ $d($r['iva']) }}</td></tr>
    <tr class="subtotal"><td>Ventas netas (sin IVA)</td><td class="derecha">{{ $d($r['ventas_netas']) }}</td></tr>
    <tr class="sangria"><td>(-) Costo de los productos vendidos</td><td class="derecha">{{ $d($r['costo_ventas']) }}</td></tr>
    <tr class="subtotal"><td>Utilidad bruta</td><td class="derecha">{{ $d($r['utilidad_bruta']) }}</td></tr>
    <tr class="sangria"><td>(-) Gastos adicionales</td><td class="derecha">{{ $d($r['gastos']) }}</td></tr>
    <tr class="subtotal"><td>UTILIDAD NETA</td><td class="derecha">{{ $d($r['utilidad_neta']) }}</td></tr>
</table>

<h2>Gastos adicionales por tipo</h2>
<table class="tabla">
    @foreach($datos['gastos_por_tipo'] as $tipo => $monto)
        <tr>
            <td>{{ $nombresGasto[$tipo] ?? ucfirst($tipo) }}</td>
            <td class="derecha">{{ $d($monto) }}</td>
        </tr>
    @endforeach
    <tr class="subtotal"><td>Total de gastos</td><td class="derecha">{{ $d($r['gastos']) }}</td></tr>
</table>

<h2>Compras a proveedores</h2>
<table class="tabla">
    <tr><td>Compras registradas</td><td class="derecha">{{ $c['cantidad'] }}</td></tr>
    <tr><td>Total comprado</td><td class="derecha">{{ $d($c['total_comprado']) }}</td></tr>
    <tr class="sangria"><td class="gris">De ese total, a crédito</td><td class="derecha gris">{{ $d($c['a_credito']) }}</td></tr>
    <tr><td>Pagado a proveedores en el período</td><td class="derecha">{{ $d($c['pagado_proveedores']) }}</td></tr>
</table>

<h2>Flujo de caja (dinero que entró y salió)</h2>
<table class="tabla">
    <tr><td>Cobrado a clientes</td><td class="derecha">{{ $d($caja['cobrado']) }}</td></tr>
    <tr class="sangria"><td class="gris">En efectivo</td><td class="derecha gris">{{ $d($caja['cobrado_efectivo']) }}</td></tr>
    <tr class="sangria"><td class="gris">Con tarjeta</td><td class="derecha gris">{{ $d($caja['cobrado_tarjeta']) }}</td></tr>
    <tr><td>(-) Pagos a proveedores</td><td class="derecha">{{ $d($caja['pagado_proveedores']) }}</td></tr>
    <tr><td>(-) Gastos adicionales</td><td class="derecha">{{ $d($caja['gastos']) }}</td></tr>
    <tr class="subtotal"><td>FLUJO NETO</td><td class="derecha">{{ $d($caja['neto']) }}</td></tr>
</table>

<h2>Deudas con proveedores (al {{ now()->format('d/m/Y') }})</h2>
<table class="tabla">
    <tr><td>Compras con saldo pendiente</td><td class="derecha">{{ $deudas['compras_pendientes'] }}</td></tr>
    <tr><td>Total adeudado</td><td class="derecha">{{ $d($deudas['total_adeudado']) }}</td></tr>
    <tr><td>De ese total, vencido ({{ $deudas['compras_vencidas'] }} compras)</td><td class="derecha vencida">{{ $d($deudas['total_vencido']) }}</td></tr>
</table>

@if(count($deudas['por_proveedor']) > 0)
    <table class="tabla" style="margin-top: 8px;">
        <tr>
            <th>Proveedor</th>
            <th class="derecha">Compras</th>
            <th class="derecha">Saldo</th>
            <th class="derecha">Vencido</th>
        </tr>
        @foreach($deudas['por_proveedor'] as $fila)
            <tr>
                <td>{{ $fila['proveedor'] }}</td>
                <td class="derecha">{{ $fila['compras'] }}</td>
                <td class="derecha">{{ $d($fila['saldo']) }}</td>
                <td class="derecha">{{ $d($fila['vencido']) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if(count($datos['mas_vendidos']) > 0)
    <h2>Productos más vendidos</h2>
    <table class="tabla">
        <tr>
            <th>Producto</th>
            <th class="derecha">Unidades</th>
            <th class="derecha">Ingresos</th>
            <th class="derecha">Utilidad</th>
        </tr>
        @foreach($datos['mas_vendidos'] as $p)
            <tr>
                <td>{{ $p['producto'] }}</td>
                <td class="derecha">{{ $p['unidades'] }}</td>
                <td class="derecha">{{ $d($p['ingresos']) }}</td>
                <td class="derecha">{{ $d($p['utilidad']) }}</td>
            </tr>
        @endforeach
    </table>
@endif

<div class="pie">
    Los ingresos incluyen IVA; la utilidad se calcula sobre ventas sin IVA.
    El estado de resultados usa lo facturado en el período; el flujo de caja, lo efectivamente cobrado y pagado.
</div>

</body>
</html>
