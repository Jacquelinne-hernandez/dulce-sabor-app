@extends('emails.layout')

@section('titulo', 'Cuenta creada')
@section('previo', 'Su cuenta ya está lista. Deberá cambiar la contraseña en su primer ingreso.')

@section('pie', 'Si no esperaba este mensaje, puede ignorarlo.')

@section('contenido')
    <h1 style="margin:0 0 6px;font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-size:26px;line-height:1.25;color:#50216e;">
        ¡Bienvenido(a), {{ $usuario->nombre }}!
    </h1>
    <p style="margin:0 0 22px;color:#77518c;">
        El administrador creó su cuenta en {{ config('app.name') }}.
    </p>

    {{-- Datos de la cuenta --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color:#f9f0fc;border:1px solid #dcb5ec;border-radius:18px;">
        <tr>
            <td style="padding:16px 20px 6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#77518c;">Nombre</td>
        </tr>
        <tr>
            <td style="padding:0 20px 12px;font-weight:700;color:#50216e;">{{ $usuario->nombre }} {{ $usuario->apellido }}</td>
        </tr>
        <tr>
            <td style="padding:4px 20px 6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#77518c;">Correo de acceso</td>
        </tr>
        <tr>
            <td style="padding:0 20px {{ $usuario->rol ? '12px' : '18px' }};font-weight:700;color:#50216e;">{{ $usuario->email }}</td>
        </tr>
        @if($usuario->rol)
            <tr>
                <td style="padding:4px 20px 6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#77518c;">Rol</td>
            </tr>
            <tr>
                <td style="padding:0 20px 18px;font-weight:700;color:#50216e;">{{ $usuario->rol->nombre }}</td>
            </tr>
        @endif
    </table>

    {{-- Cómo ingresar --}}
    <h2 style="margin:28px 0 12px;font-family:'Playfair Display',Georgia,'Times New Roman',serif;font-size:18px;color:#50216e;">
        Cómo ingresar por primera vez
    </h2>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="34" valign="top" style="padding:0 0 12px;">
                <div style="width:24px;height:24px;line-height:24px;text-align:center;border-radius:50%;background-color:#ecd9f8;color:#8434a1;font-size:13px;font-weight:700;">1</div>
            </td>
            <td style="padding:0 0 12px;">Solicite su contraseña inicial al administrador.</td>
        </tr>
        <tr>
            <td width="34" valign="top" style="padding:0 0 12px;">
                <div style="width:24px;height:24px;line-height:24px;text-align:center;border-radius:50%;background-color:#ecd9f8;color:#8434a1;font-size:13px;font-weight:700;">2</div>
            </td>
            <td style="padding:0 0 12px;">Inicie sesión con el correo indicado arriba.</td>
        </tr>
        <tr>
            <td width="34" valign="top" style="padding:0;">
                <div style="width:24px;height:24px;line-height:24px;text-align:center;border-radius:50%;background-color:#ecd9f8;color:#8434a1;font-size:13px;font-weight:700;">3</div>
            </td>
            <td style="padding:0;">
                El sistema le pedirá crear una contraseña nueva: mínimo 8 caracteres, con mayúscula,
                minúscula, número y un carácter especial.
            </td>
        </tr>
    </table>

    {{-- Botón --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:30px auto 4px;">
        <tr>
            <td align="center" style="border-radius:50px;background-color:#a94bc1;background-image:linear-gradient(110deg,#c16ed4,#a33cbb,#8429a3);">
                <a href="{{ url('/login') }}"
                   style="display:inline-block;padding:14px 34px;font-family:'DM Sans',Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:50px;">
                    Ir al inicio de sesión
                </a>
            </td>
        </tr>
    </table>
@endsection
