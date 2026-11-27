<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('titulo')</title>
</head>
<body style="margin:0;padding:0;background-color:#f5e8fa;">

    {{-- Texto de vista previa (lo que se ve junto al asunto en la bandeja) --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        @yield('previo')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5e8fa;">
        <tr>
            <td align="center" style="padding:32px 12px;">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;">

                    {{-- Encabezado --}}
                    <tr>
                        <td align="center" style="background-color:#50216e;background-image:linear-gradient(110deg,#50216e 0%,#7a2f99 60%,#8f3cae 100%);padding:30px 24px 26px;border-radius:24px 24px 0 0;">
                            <div style="font-size:30px;line-height:1;">🧁</div>
                            <div style="font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-size:28px;font-weight:700;color:#ffffff;margin-top:10px;">
                                {{ config('app.name') }}
                            </div>
                            <div style="font-family:'DM Sans',Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#f0d5fa;margin-top:6px;">
                                Pastelería
                            </div>
                        </td>
                    </tr>

                    {{-- Contenido --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:34px 32px 30px;border-left:1px solid #dcb5ec;border-right:1px solid #dcb5ec;font-family:'DM Sans',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#50216e;">
                            @yield('contenido')
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td align="center" style="background-color:#fffaff;padding:20px 32px;border:1px solid #dcb5ec;border-top:1px dashed #dcb5ec;border-radius:0 0 24px 24px;font-family:'DM Sans',Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#77518c;">
                            @yield('pie', 'Este es un mensaje automático, por favor no responda a este correo.')
                            <br>
                            © {{ date('Y') }} {{ config('app.name') }}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
