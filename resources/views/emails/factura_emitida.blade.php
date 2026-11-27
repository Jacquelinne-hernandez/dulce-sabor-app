<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura emitida</title>
</head>
<body style="margin:0;padding:24px;background:#f7f0fb;font-family:Arial,Helvetica,sans-serif;color:#3b1f4d;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;padding:28px;border:1px solid #e3c5f0;">
        <h1 style="margin:0 0 12px;font-size:22px;color:#7a2f9a;">{{ config('app.name') }}</h1>

        <p>Hola{{ $factura->cliente ? ', ' . $factura->cliente->nombre : '' }}. ¡Gracias por su compra!</p>

        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr>
                <td style="padding:6px 0;color:#8a7597;">Factura</td>
                <td style="padding:6px 0;text-align:right;"><strong>{{ $factura->numero_factura }}</strong></td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#8a7597;">Fecha</td>
                <td style="padding:6px 0;text-align:right;">{{ optional($factura->fecha_emision)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#8a7597;">Total (IVA incluido)</td>
                <td style="padding:6px 0;text-align:right;"><strong>${{ number_format((float) $factura->total, 2) }}</strong></td>
            </tr>
        </table>

        <p style="font-size:12px;color:#8a7597;margin-top:28px;">
            Conserve este mensaje como comprobante de su compra.
        </p>
    </div>
</body>
</html>
