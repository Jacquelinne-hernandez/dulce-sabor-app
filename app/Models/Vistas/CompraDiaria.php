<?php

namespace App\Models\Vistas;

// v_compras_diarias: compras por día.
class CompraDiaria extends VistaSoloLectura
{
    protected $table = 'v_compras_diarias';

    protected $casts = [
        'fecha' => 'date',
        'compras' => 'integer',
        'total_comprado' => 'decimal:2',
        'total_credito' => 'decimal:2',
    ];
}
