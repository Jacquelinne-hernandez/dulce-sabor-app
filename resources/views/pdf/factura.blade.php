@php
    $d = fn ($valor) => '$' . number_format((float) $valor, 2);
    $pedido = $factura->pedido;
    $cliente = $factura->cliente;
    $pagos = $factura->pagos->where('estado', 'aplicado');
    $mesasReserva = $pedido->reserva?->mesas ?? collect();

    $tipos = [
        'local' => 'Consumo en el local' . ($pedido->mesa ? ' (mesa ' . $pedido->mesa->numero . ')' : ''),
        'llevar' => 'Para llevar',
        'reserva' => 'Reserva' . ($mesasReserva->isNotEmpty() ? ' (mesas ' . $mesasReserva->pluck('numero')->implode(', ') . ')' : ''),
    ];

    $estados = ['emitida' => 'Pendiente de pago', 'pagada' => 'Pagada', 'anulada' => 'ANULADA'];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        @page { margin: 36px 42px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .encabezado td { vertical-align: top; }
        .derecha { text-align: right; }
        .centro { text-align: center; }
        .gris { color: #666; }
        .logo { height: 54px; }
        .caja { border: 1px solid #999; padding: 7px 9px; }
        .detalle th { border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 5px 4px; text-align: left; font-size: 10px; }
        .detalle td { padding: 5px 4px; border-bottom: 1px solid #ddd; }
        .totales td { padding: 3px 4px; }
        .totales .total td { border-top: 1px solid #333; font-size: 13px; font-weight: bold; padding-top: 6px; }
        .anulada { color: #b00020; font-size: 26px; font-weight: bold; text-align: center; margin: 8px 0; border: 2px solid #b00020; padding: 4px; }
        .pie { margin-top: 26px; font-size: 10px; text-align: center; }
        .espacio { height: 10px; }
    </style>
</head>
<body>

<table class="encabezado">
    <tr>
        <td style="width: 60%;">
            @if($logo)
                <img class="logo" src="{{ $logo }}" alt="Dulce Sabor">
            @else
                <h1>Dulce Sabor</h1>
            @endif
        </td>
        <td class="derecha">
            <h1>FACTURA</h1>
            <div><strong>{{ $factura->numero_factura }}</strong></div>
            <div class="gris">{{ $factura->fecha_emision->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

@if($factura->estado === 'anulada')
    <div class="anulada">FACTURA ANULADA</div>
@endif

<div class="espacio"></div>

<table>
    <tr>
        <td class="caja" style="width: 49%;">
            <strong>Cliente</strong><br>
            @if($cliente)
                {{ $cliente->nombre_completo }}<br>
                <span class="gris">Código: {{ $cliente->codigo }}</span>
            @else
                Consumidor final
            @endif
        </td>
        <td style="width: 2%;"></td>
        <td class="caja" style="width: 49%;">
            <strong>Pedido #{{ $pedido->id }}</strong>
            @if($pedido->orden) · Orden {{ $pedido->orden->numero_orden }} @endif
            <br>
            {{ $tipos[$pedido->tipo] ?? $pedido->tipo }}<br>
            <span class="gris">Estado: {{ $estados[$factura->estado] ?? $factura->estado }}</span>
        </td>
    </tr>
</table>

<div class="espacio"></div>

<table class="detalle">
    <thead>
    <tr>
        <th>Descripción</th>
        <th class="centro" style="width: 48px;">Cant.</th>
        <th class="derecha" style="width: 80px;">P. unitario</th>
        <th class="derecha" style="width: 80px;">Subtotal</th>
    </tr>
    </thead>
    <tbody>
    @foreach($factura->detalles as $linea)
        <tr>
            <td>{{ $linea->nombre_producto }}</td>
            <td class="centro">{{ $linea->cantidad }}</td>
            <td class="derecha">{{ $d($linea->precio_unitario) }}</td>
            <td class="derecha">{{ $d($linea->subtotal) }}</td>
        </tr>
    @endforeach

    @foreach($mesasReserva as $mesa)
        <tr>
            <td>Uso de mesa {{ $mesa->numero }} ({{ rtrim(rtrim(number_format((float) $mesa->pivot->horas, 1), '0'), '.') }} h)</td>
            <td class="centro">1</td>
            <td class="derecha">{{ $d($mesa->pivot->subtotal) }}</td>
            <td class="derecha">{{ $d($mesa->pivot->subtotal) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="espacio"></div>

<table>
    <tr>
        <td style="width: 52%; vertical-align: top;">
            @if($pagos->isNotEmpty())
                <strong>Pagos</strong>
                <table class="totales">
                    @foreach($pagos as $pago)
                        <tr>
                            <td>
                                {{ $pago->fecha_pago->format('d/m/Y H:i') }}
                                · @if($pago->metodo === 'tarjeta') Tarjeta {{ $pago->tarjeta_marca }} ****{{ $pago->tarjeta_ultimos4 }}
                                @else Efectivo @endif
                            </td>
                            <td class="derecha">{{ $d($pago->monto) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if($cliente && $factura->estado !== 'anulada')
                <div class="espacio"></div>
                <span class="gris">
                    Puntos ganados: {{ $factura->puntos_ganados }}
                    @if($factura->puntos_canjeados > 0) · Canjeados: {{ $factura->puntos_canjeados }} @endif
                    <br>Puntos acumulados: {{ $cliente->puntos }}
                </span>
            @endif
        </td>
        <td style="width: 48%; vertical-align: top;">
            <table class="totales">
                <tr><td>Subtotal productos</td><td class="derecha">{{ $d($factura->subtotal) }}</td></tr>
                @if((float) $factura->cargo_mesas > 0)
                    <tr><td>Uso de mesas</td><td class="derecha">{{ $d($factura->cargo_mesas) }}</td></tr>
                @endif
                @if((float) $factura->descuento > 0)
                    <tr><td>Descuento por puntos</td><td class="derecha">-{{ $d($factura->descuento) }}</td></tr>
                @endif
                <tr class="total"><td>TOTAL</td><td class="derecha">{{ $d($factura->total) }}</td></tr>
                <tr><td class="gris">Base imponible</td><td class="derecha gris">{{ $d($factura->base_imponible) }}</td></tr>
                <tr><td class="gris">IVA (13% incluido)</td><td class="derecha gris">{{ $d($factura->iva) }}</td></tr>
                @if($factura->estado === 'emitida')
                    <tr><td><strong>Saldo pendiente</strong></td><td class="derecha"><strong>{{ $d($factura->saldo) }}</strong></td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<div class="pie">
    Atendió: {{ $factura->usuario?->nombre }} {{ $factura->usuario?->apellido }}<br>
    Los precios incluyen IVA. ¡Gracias por su compra!
</div>

</body>
</html>
