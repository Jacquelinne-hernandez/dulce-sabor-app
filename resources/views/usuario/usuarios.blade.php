@extends('layouts.app_layout')

@section('titulo', 'Usuarios | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/usuarios.css') }}">
@endpush

@section('contenido')
<div id="modulo-usuarios"
     data-url-todos="{{ route('usuarios.todos') }}"
     data-url-insertar="{{ route('usuarios.insertar') }}"
     data-url-buscar="{{ route('usuarios.buscar', ['id' => '__ID__']) }}"
     data-url-actualizar="{{ route('usuarios.actualizar', ['id' => '__ID__']) }}"
     data-url-eliminar="{{ route('usuarios.eliminar', ['id' => '__ID__']) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-user-gear me-2"></i>Usuarios</h1>
            <p class="text-muted mb-0">Cuentas del personal que utiliza el sistema.</p>
        </div>
        <button type="button" class="btn btn-primary" data-accion="nuevo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo usuario
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-usuarios" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-usuarios">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-usuarios" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" data-titulo-modal>Nuevo usuario</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-usuarios" novalidate>
                        <div class="alert alert-danger d-none" role="alert" data-errores></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="usuarios-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="usuarios-nombre" name="nombre" required maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="usuarios-apellido" name="apellido" required maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="usuarios-email" name="email" required maxlength="100" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="usuarios-telefono" name="telefono" maxlength="9" placeholder="0000-0000">
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="usuarios-id_rol" name="id_rol" required>
                                    <option value="">Seleccione...</option>
                            @foreach($roles as $rol)
                                    <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-id_estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="usuarios-id_estado" name="id_estado" required>
                                    <option value="">Seleccione...</option>
                            @foreach($estados as $estado)
                                    <option value="{{ $estado->id }}">{{ ucfirst(strtolower($estado->estado)) }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="usuarios-password" name="password" minlength="8" maxlength="255" autocomplete="new-password">
                                <div class="form-text"><span data-ayuda-password class="d-none">Deje en blanco para conservar la contraseña actual. </span>Mínimo 8 caracteres, con mayúscula, minúscula, número y un carácter especial (! @ # $ % &amp; *). El usuario deberá cambiarla en su primer ingreso.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="usuarios-password_confirmation" class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control" id="usuarios-password_confirmation" name="password_confirmation" minlength="8" maxlength="255" autocomplete="new-password">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="form-usuarios" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/usuarios.js') }}"></script>
@endpush
