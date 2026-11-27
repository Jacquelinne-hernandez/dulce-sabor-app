<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta creada</title>
</head>
<body style="margin:0;padding:24px;background:#f7f0fb;font-family:Arial,Helvetica,sans-serif;color:#3b1f4d;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;padding:28px;border:1px solid #e3c5f0;">
        <h1 style="margin:0 0 12px;font-size:22px;color:#7a2f9a;">{{ config('app.name') }}</h1>

        <p>Hola, <strong>{{ $usuario->nombre }} {{ $usuario->apellido }}</strong>.</p>

        <p>El administrador creó su cuenta en el sistema con el correo
            <strong>{{ $usuario->email }}</strong>.</p>

        <p>La contraseña inicial se la entregará el administrador. Por su
            seguridad, el sistema le pedirá <strong>cambiarla en su primer ingreso</strong>.</p>

        <p style="margin-top:24px;">
            <a href="{{ url('/login') }}"
               style="background:#963db5;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:10px;display:inline-block;">
                Ir al inicio de sesión
            </a>
        </p>

        <p style="font-size:12px;color:#8a7597;margin-top:28px;">
            Si no esperaba este mensaje, puede ignorarlo.
        </p>
    </div>
</body>
</html>
