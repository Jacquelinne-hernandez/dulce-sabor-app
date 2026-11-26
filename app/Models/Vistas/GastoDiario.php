<?php

namespace App\Models\Vistas;

// v_gastos_diarios: gastos adicionales por día y tipo.
class GastoDiario extends VistaSoloLectura
{
    protected $table = 'v_gastos_diarios';

    protected $casts = [
        'fecha' => 'date',
        'tipo' => 'string',
        'gastos' => 'integer',
        'monto' => 'decimal:2',
    ];
}
