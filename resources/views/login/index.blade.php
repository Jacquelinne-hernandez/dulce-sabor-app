
@extends('layouts.auth_layout')

@section('titulo', 'Iniciar sesión | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('contenido')

    <div class="dulce-login">

        <main class="dulce-login__contenedor">

            {{-- PANEL IZQUIERDO --}}
            <section class="dulce-login__portada"
                     aria-label="Identidad de Dulce Sabor Sys">

                <div class="dulce-login__marca">

                    <div class="dulce-login__icono" aria-hidden="true">
                        <i class="fa-solid fa-cake-candles"></i>
                    </div>

                    <h1 class="dulce-login__nombre">
                        Dulce Sabor Sys
                    </h1>

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

                    <p class="dulce-login__frase">
                        Endulzando<br>
                        cada momento
                        <span>♡</span>
                    </p>

                </div>

                <p class="dulce-login__pie-portada">
                    <i class="fa-regular fa-heart"></i>
                    Hecho con amor y un toque de dulzura
                </p>

            </section>


            {{-- PANEL DERECHO --}}
            <section class="dulce-login__panel">

                <div class="dulce-login__adorno" aria-hidden="true">
                    <span></span>
                    <i class="fa-regular fa-heart"></i>
                    <span></span>
                </div>

                <h2 class="dulce-login__bienvenida">
                    Bienvenido
                </h2>

                <p class="dulce-login__descripcion">
                    INGRESA A TU SISTEMA
                </p>

                <div class="dulce-login__separador" aria-hidden="true">
                    <span></span>
                    <i class="fa-regular fa-heart"></i>
                    <span></span>
                </div>


                {{-- FORMULARIO ORIGINAL --}}
                <form action="{{ route('login') }}"
                      method="POST"
                      id="formLogin">

                    @csrf

                    {{-- CORREO --}}
                    <div class="dulce-login__campo">

                        <label for="email" class="dulce-login__label">
                        <span class="dulce-login__icono-label">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="dulce-login__input @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="Ingrese su correo"
                            autocomplete="username"
                            aria-describedby="email-error"
                            required
                            autofocus
                        >

                        @error('email')
                        <div class="dulce-login__error"
                             id="email-error"
                             role="alert">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>


                    {{-- CONTRASEÑA --}}
                    <div class="dulce-login__campo">

                        <label for="password" class="dulce-login__label">
                        <span class="dulce-login__icono-label">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                            Contraseña
                        </label>

                        <div class="dulce-login__input-contenedor">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="dulce-login__input dulce-login__input--password @error('password') is-invalid @enderror"
                                placeholder="Ingrese su contraseña"
                                autocomplete="current-password"
                                aria-describedby="password-error"
                                required
                            >

                            <button
                                type="button"
                                class="dulce-login__ver-password"
                                id="togglePassword"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false"
                            >
                                <svg
                                    id="iconoPassword"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>

                        </div>

                        @error('password')
                        <div class="dulce-login__error"
                             id="password-error"
                             role="alert">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>


                    {{-- OPCIONES --}}
                    <div class="dulce-login__opciones">

                        <label for="remember" class="dulce-login__recordarme">

                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                value="1"
                                @checked(old('remember'))
                            >

                            <span>Recordarme</span>

                        </label>

                        <a href="#" class="dulce-login__enlace">
                            ¿Olvidaste tu contraseña?
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>


                    {{-- BOTÓN --}}
                    <button
                        type="submit"
                        class="dulce-login__boton"
                        id="btnLogin"
                    >

                    <span class="dulce-login__boton-icono" aria-hidden="true">
                        <i class="fa-solid fa-cake-candles"></i>
                    </span>

                        <span id="textoBoton">
                        INICIAR SESIÓN
                    </span>

                        <span class="dulce-login__boton-flecha" aria-hidden="true">
                        →
                    </span>

                    </button>

                </form>


                {{-- PIE DEL FORMULARIO --}}
                <div class="dulce-login__pie">

                    <div class="dulce-login__pie-decoracion" aria-hidden="true">
                        <span></span>
                        <i class="fa-regular fa-heart"></i>
                        <span></span>
                    </div>

                    <p>Tu pasión, nuestra inspiración</p>

                </div>

            </section>

        </main>

        {{-- DECORACIONES EXTERIORES --}}
        <span class="dulce-login__corazon dulce-login__corazon--uno" aria-hidden="true">♡</span>
        <span class="dulce-login__corazon dulce-login__corazon--dos" aria-hidden="true">♡</span>
        <span class="dulce-login__corazon dulce-login__corazon--tres" aria-hidden="true">♡</span>

        <span class="dulce-login__pastel-decorativo dulce-login__pastel--uno" aria-hidden="true">🧁</span>
        <span class="dulce-login__pastel-decorativo dulce-login__pastel--dos" aria-hidden="true">🧁</span>

    </div>

@endsection


{{-- SE CONSERVA EL JAVASCRIPT EXISTENTE --}}
@push('scripts')
    <script src="{{ asset('js/login.js') }}"></script>
@endpush
