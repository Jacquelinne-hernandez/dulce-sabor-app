@extends('layouts.auth_layout')

@section('titulo', 'Cambiar contraseña | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        .dulce-login__requisitos { list-style: none; margin: 4px 0 22px; padding: 0; font-size: 14px; color: #7a5a8c; }
        .dulce-login__requisitos li { display: flex; align-items: center; gap: 8px; padding: 2px 0; }
        .dulce-login__requisitos li i { width: 16px; text-align: center; color: #c9a2dc; }
        .dulce-login__requisitos li.cumple { color: #2f7d4f; }
        .dulce-login__requisitos li.cumple i { color: #2f7d4f; }
        .dulce-login__salir { text-align: center; margin-top: 14px; }
        .dulce-login__salir button { background: none; border: 0; padding: 0; }
    </style>
@endpush

@section('contenido')

    <div class="dulce-login">

        <main class="dulce-login__contenedor">

            {{-- PANEL IZQUIERDO --}}
            <section class="dulce-login__portada" aria-label="Identidad de Dulce Sabor Sys">
                <div class="dulce-login__marca">
                    <div class="dulce-login__icono" aria-hidden="true">
                        <i class="fa-solid fa-cake-candles"></i>
                    </div>

                    <h1 class="dulce-login__nombre">Dulce Sabor Sys</h1>

                    <div class="dulce-login__decoracion" aria-hidden="true">
                        <span></span>
                        <i class="fa-regular fa-heart"></i>
                        <span></span>
                    </div>

                    <p class="dulce-login__subtitulo">
                        SISTEMA DE GESTIÓN
                        <br>
                        PASTELERÍA
                    </p>
                </div>

                <p class="dulce-login__pie-portada">
                    <i class="fa-regular fa-heart"></i>
                    Hecho con amor y un toque de dulzura
                </p>
            </section>

            {{-- PANEL DERECHO --}}
            <section class="dulce-login__panel">

                <h2 class="dulce-login__bienvenida">
                    {{ $obligatorio ? 'Cree su contraseña' : 'Cambiar contraseña' }}
                </h2>

                <p class="dulce-login__descripcion">
                    @if($obligatorio)
                        POR SEGURIDAD, DEBE CAMBIARLA PARA CONTINUAR
                    @else
                        ELIJA UNA NUEVA CONTRASEÑA
                    @endif
                </p>

                <form action="{{ route('password.actualizar') }}" method="POST" id="formPassword" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="dulce-login__campo">
                        <label for="password_actual" class="dulce-login__label">
                            <span class="dulce-login__icono-label"><i class="fa-solid fa-key"></i></span>
                            Contraseña actual
                        </label>
                        <input type="password" id="password_actual" name="password_actual"
                               class="dulce-login__input @error('password_actual') is-invalid @enderror"
                               autocomplete="current-password" required autofocus>
                        @error('password_actual')
                            <div class="dulce-login__error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="dulce-login__campo">
                        <label for="password" class="dulce-login__label">
                            <span class="dulce-login__icono-label"><i class="fa-solid fa-lock"></i></span>
                            Nueva contraseña
                        </label>
                        <input type="password" id="password" name="password"
                               class="dulce-login__input @error('password') is-invalid @enderror"
                               autocomplete="new-password" required maxlength="72">
                        @error('password')
                            <div class="dulce-login__error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="dulce-login__campo">
                        <label for="password_confirmation" class="dulce-login__label">
                            <span class="dulce-login__icono-label"><i class="fa-solid fa-lock"></i></span>
                            Confirmar nueva contraseña
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="dulce-login__input" autocomplete="new-password" required maxlength="72">
                    </div>

                    <ul class="dulce-login__requisitos" id="requisitosPassword" aria-live="polite">
                        <li data-regla="largo"><i class="fa-regular fa-circle"></i>Mínimo 8 caracteres</li>
                        <li data-regla="minuscula"><i class="fa-regular fa-circle"></i>Una letra minúscula</li>
                        <li data-regla="mayuscula"><i class="fa-regular fa-circle"></i>Una letra mayúscula</li>
                        <li data-regla="numero"><i class="fa-regular fa-circle"></i>Un número</li>
                        <li data-regla="especial"><i class="fa-regular fa-circle"></i>Un carácter especial (! @ # $ % &amp; *)</li>
                        <li data-regla="coincide"><i class="fa-regular fa-circle"></i>Las dos contraseñas coinciden</li>
                    </ul>

                    <button type="submit" class="dulce-login__boton" id="btnPassword">
                        <span class="dulce-login__boton-icono" aria-hidden="true">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <span>GUARDAR CONTRASEÑA</span>
                        <span class="dulce-login__boton-flecha" aria-hidden="true">→</span>
                    </button>
                </form>

                <form action="{{ route('logout') }}" method="POST" class="dulce-login__salir">
                    @csrf
                    <button type="submit" class="dulce-login__enlace">Cerrar sesión</button>
                </form>

            </section>

        </main>

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nueva = document.getElementById('password');
            const confirmacion = document.getElementById('password_confirmation');
            const lista = document.getElementById('requisitosPassword');

            if (!nueva || !lista) {
                return;
            }

            function evaluar() {
                const v = nueva.value;
                const reglas = {
                    largo: v.length >= 8,
                    minuscula: /\p{Ll}/u.test(v),
                    mayuscula: /\p{Lu}/u.test(v),
                    numero: /\d/.test(v),
                    especial: /[^\p{L}\p{N}\s]/u.test(v) && !/\s/.test(v),
                    coincide: v !== '' && v === confirmacion.value
                };

                lista.querySelectorAll('li').forEach(function (li) {
                    const cumple = !!reglas[li.dataset.regla];
                    const icono = li.querySelector('i');

                    li.classList.toggle('cumple', cumple);
                    icono.className = cumple ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
                });
            }

            nueva.addEventListener('input', evaluar);
            confirmacion.addEventListener('input', evaluar);
            evaluar();
        });
    </script>
@endpush
