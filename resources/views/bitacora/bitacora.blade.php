@extends('layouts.app_layout')

@section('titulo', 'Bitácora | Dulce Sabor Sys')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/bitacora.css') }}">
@endpush

@php
    $colorEvento = [
        'LOGIN_OK' => 'success',
        'LOGIN_FALLIDO' => 'danger',
        'LOGOUT' => 'secondary',
        'CREADO' => 'primary',
        'ACTUALIZADO' => 'warning',
        'ELIMINADO' => 'danger',
    ];
@endphp

@section('contenido')
    <div id="modulo-bitacora">

        <div class="pagina-hero">
            <div>
                <h1><i class="fa-solid fa-clipboard-list"></i>Bitácora</h1>
                <p>Registro de auditoría de las acciones realizadas en el sistema.</p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('bitacora') }}" class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="evento" class="form-label">Evento</label>
                        <select class="form-select" id="evento" name="evento">
                            <option value="">Todos</option>
                            @foreach($eventos as $evento)
                                <option value="{{ $evento }}" @selected(request('evento') === $evento)>
                                    {{ str_replace('_', ' ', $evento) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_desde" class="form-label">Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                               value="{{ request('fecha_desde') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_hasta" class="form-label">Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                               value="{{ request('fecha_hasta') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fa-solid fa-filter me-1"></i>Filtrar
                        </button>
                        <a href="{{ route('bitacora') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5 col-lg-4">
                        <input type="search" class="form-control" placeholder="Buscar en los resultados..."
                               data-filtro="#tabla-bitacora" aria-label="Buscar">
                    </div>
                    <div class="col-md-7 col-lg-8 text-md-end text-muted small">
                        {{ $bitacoras->count() }} registro(s)
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" id="tabla-bitacora">
                    <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Módulo</th>
                        <th>Registro</th>
                        <th>Descripción</th>
                        <th>IP</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($bitacoras as $registro)
                        <tr>
                            <td class="text-nowrap">{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $registro->usuario_nombre ?: '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $colorEvento[$registro->evento] ?? 'secondary' }}">
                                    {{ str_replace('_', ' ', $registro->evento) }}
                                </span>
                            </td>
                            <td>{{ $registro->modulo ?: '—' }}</td>
                            <td>{{ $registro->registro_id ?: '—' }}</td>
                            <td>{{ $registro->descripcion }}</td>
                            <td class="text-nowrap">{{ $registro->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay registros para mostrar.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
