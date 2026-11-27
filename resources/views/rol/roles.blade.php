@extends('layouts.app_layout')

@section('titulo', 'Roles | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/roles.css') }}">
@endpush

@section('contenido')
<div id="modulo-roles"
     data-url-todos="{{ route('roles.todos') }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fa-solid fa-user-shield me-2"></i>Roles</h1>
            <p class="text-muted mb-0">Perfiles de acceso del sistema. Son fijos: esta pantalla es solo de consulta.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <input type="search" class="form-control" placeholder="Buscar..."
                           data-filtro="#tabla-roles" aria-label="Buscar">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-roles">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/roles.js') }}"></script>
@endpush
