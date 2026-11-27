@extends('emails.layout')

@php
    $dinero = fn ($valor) => '$' . number_format((float) $valor, 2);
@endphp

@section('titulo', 'Factura emitida')
@section('previo', 'Factura ' . $factura->numero_factura . ' por ' . $dinero($factura->total) . '. ¡Gracias por su compra!')

@section('pie', 'Conserve este mensaje como comprobante de su compra.')

@section('contenido')
    <h1 style="margin:0 0 6px;font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-size:26px;line-height:1.25;color:#50216e;">
        ¡Gracias por su compra{{ $factura->cliente ? ', ' . $factura->cliente->nombre : '' }}!
    </h1>
    <p style="margin:0 0 22px;color:#77518c;">
        Esta es la factura de su pedido en {{ config('app.name') }}.
    </p>

    {{-- Total destacado --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color:#f9f0fc;background-image:linear-gradient(120deg,#fbf3ff 0%,#f1ddfa 55%,#ecc9f2 100%);border:1px solid #dcb5ec;border-radius:18px;">
        <tr>
            <td align="center" style="padding:20px 20px 4px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#77518c;">
                Total (IVA incluido)
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:0 20px 6px;font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-size:38px;font-weight:700;color:#d6336c;">
                {{ $dinero($factura->total) }}
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:0 20px 18px;font-size:13px;color:#77518c;">
                Factura <strong style="color:#50216e;">{{ $factura->numero_factura }}</strong>
                · {{ optional($factura->fecha_emision)->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    {{-- Detalle --}}
    <h2 style="margin:28px 0 10px;font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-size:18px;color:#50216e;">
        Detalle
    </h2>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr>
            <td style="padding:8px 10px;background-color:#f9f0fc;border-radius:10px 0 0 10px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#77518c;">Producto</td>
            <td align="center" width="50" style="padding:8px 4px;background-color:#f9f0fc;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#77518c;">Cant.</td>
            <td align="right" width="90" style="padding:8px 10px;background-color:#f9f0fc;border-radius:0 10px 10px 0;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#77518c;">Subtotal</td>
        </tr>
        @foreach($factura->detalles as $linea)
            <tr>
                <td style="padding:10px;border-bottom:1px dashed #f3d6e4;">{{ $linea->nombre_producto }}</td>
                <td align="center" style="padding:10px 4px;border-bottom:1px dashed #f3d6e4;color:#77518c;">{{ $linea->cantidad }}</td>
                <td align="right" style="padding:10px;border-bottom:1px dashed #f3d6e4;font-weight:700;">{{ $dinero($linea->subtotal) }}</td>
            </tr>
        @endforeach
    </table>

    {{-- Totales --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;">
        <tr>
            <td style="padding:4px 10px;color:#77518c;">Subtotal productos</td>
            <td align="right" style="padding:4px 10px;">{{ $dinero($factura->subtotal) }}</td>
        </tr>
        @if((float) $factura->cargo_mesas > 0)
            <tr>
                <td style="padding:4px 10px;color:#77518c;">Uso de mesas</td>
                <td align="right" style="padding:4px 10px;">{{ $dinero($factura->cargo_mesas) }}</td>
            </tr>
        @endif
        @if((float) $factura->descuento > 0)
            <tr>
                <td style="padding:4px 10px;color:#77518c;">Descuento por puntos</td>
                <td align="right" style="padding:4px 10px;color:#1f9d63;">-{{ $dinero($factura->descuento) }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:10px 10px 4px;font-weight:700;border-top:2px solid #dcb5ec;">Total</td>
            <td align="right" style="padding:10px 10px 4px;font-weight:700;font-size:17px;color:#d6336c;border-top:2px solid #dcb5ec;">{{ $dinero($factura->total) }}</td>
        </tr>
    </table>

    {{-- Puntos de fidelidad --}}
    @if($factura->cliente && (int) $factura->puntos_ganados > 0)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
            <tr>
                <td align="center" style="padding:12px 16px;background-color:#d6f5e6;border-radius:14px;font-size:14px;color:#1f9d63;">
                    ✨ Ganó <strong>{{ (int) $factura->puntos_ganados }}</strong> puntos con esta compra
                    · Acumulados: <strong>{{ (int) $factura->cliente->puntos }}</strong>
                </td>
            </tr>
        </table>
    @endif
@endsection
