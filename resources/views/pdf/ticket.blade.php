@php
    $d = fn ($valor) => '$' . number_format((float) $valor, 2);
    $pedido = $factura->pedido;
    $cliente = $factura->cliente;
    $pagos = $factura->pagos->where('estado', 'aplicado');
    $mesasReserva = $pedido->reserva?->mesas ?? collect();
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $factura->numero_factura }}</title>
    <style>
        @page { margin: 12px 10px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        .centro { text-align: center; }
        .derecha { text-align: right; }
        .logo { height: 40px; }
        .linea { border-top: 1px dashed #000; margin: 6px 0; }
        td { padding: 1px 0; vertical-align: top; }
        .total td { font-size: 12px; font-weight: bold; padding-top: 3px; }
        .anulada { text-align: center; font-weight: bold; font-size: 12px; margin: 4px 0; }
    </style>
</head>
<body>

<div class="centro">
    @if($logo)
        <img class="logo" src="{{ $logo }}" alt="Dulce Sabor"><br>
    @else
        <strong style="font-size: 13px;">Dulce Sabor</strong><br>
    @endif
    <strong>{{ $factura->numero_factura }}</strong><br>
    {{ $factura->fecha_emision->format('d/m/Y H:i') }}
</div>

@if($factura->estado === 'anulada')
    <div class="anulada">*** ANULADA ***</div>
@endif

<div class="linea"></div>

<div>
    Cliente: {{ $cliente ? $cliente->nombre_completo : 'Consumidor final' }}<br>
    @if($cliente) Código: {{ $cliente->codigo }}<br> @endif
    Pedido #{{ $pedido->id }}
    @if($pedido->tipo === 'local' && $pedido->mesa) · Mesa {{ $pedido->mesa->numero }}
    @elseif($pedido->tipo === 'llevar') · Para llevar
    @elseif($pedido->tipo === 'reserva') · Reserva @endif
</div>

<div class="linea"></div>

<table>
    @foreach($factura->detalles as $linea)
        <tr>
            <td colspan="2">{{ $linea->nombre_producto }}</td>
        </tr>
        <tr>
            <td>{{ $linea->cantidad }} x {{ $d($linea->precio_unitario) }}</td>
            <td class="derecha">{{ $d($linea->subtotal) }}</td>
        </tr>
    @endforeach

    @foreach($mesasReserva as $mesa)
        <tr>
            <td>Mesa {{ $mesa->numero }} ({{ rtrim(rtrim(number_format((float) $mesa->pivot->horas, 1), '0'), '.') }} h)</td>
            <td class="derecha">{{ $d($mesa->pivot->subtotal) }}</td>
        </tr>
    @endforeach
</table>

<div class="linea"></div>

<table>
    <tr><td>Subtotal</td><td class="derecha">{{ $d($factura->subtotal) }}</td></tr>
    @if((float) $factura->cargo_mesas > 0)
        <tr><td>Uso de mesas</td><td class="derecha">{{ $d($factura->cargo_mesas) }}</td></tr>
    @endif
    @if((float) $factura->descuento > 0)
        <tr><td>Desc. puntos</td><td class="derecha">-{{ $d($factura->descuento) }}</td></tr>
    @endif
    <tr class="total"><td>TOTAL</td><td class="derecha">{{ $d($factura->total) }}</td></tr>
    <tr><td>Base imponible</td><td class="derecha">{{ $d($factura->base_imponible) }}</td></tr>
    <tr><td>IVA 13% incluido</td><td class="derecha">{{ $d($factura->iva) }}</td></tr>
</table>

@if($pagos->isNotEmpty())
    <div class="linea"></div>
    <table>
        @foreach($pagos as $pago)
            <tr>
                <td>
                    @if($pago->metodo === 'tarjeta') Tarjeta ****{{ $pago->tarjeta_ultimos4 }}
                    @else Efectivo @endif
                </td>
                <td class="derecha">{{ $d($pago->monto) }}</td>
            </tr>
            @if($pago->metodo === 'efectivo' && $pago->efectivo_recibido !== null)
                <tr>
                    <td>Recibido {{ $d($pago->efectivo_recibido) }}</td>
                    <td class="derecha">Cambio {{ $d($pago->cambio) }}</td>
                </tr>
            @endif
        @endforeach
        @if($factura->estado === 'emitida')
            <tr><td><strong>Saldo</strong></td><td class="derecha"><strong>{{ $d($factura->saldo) }}</strong></td></tr>
        @endif
    </table>
@endif

@if($cliente && $factura->estado !== 'anulada')
    <div class="linea"></div>
    <div class="centro">
        Puntos ganados: {{ $factura->puntos_ganados }}
        @if($factura->puntos_canjeados > 0) · Canjeados: {{ $factura->puntos_canjeados }} @endif
        <br>Acumulados: {{ $cliente->puntos }}
    </div>
@endif

<div class="linea"></div>
<div class="centro">¡Gracias por su compra!</div>

</body>
</html>
