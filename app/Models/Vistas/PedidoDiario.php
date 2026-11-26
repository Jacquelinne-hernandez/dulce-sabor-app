<?php

namespace App\Models\Vistas;

// v_pedidos_diarios: pedidos registrados por día y tipo.
class PedidoDiario extends VistaSoloLectura
{
    protected $table = 'v_pedidos_diarios';

    protected $casts = [
        'fecha' => 'date',
        'tipo' => 'string',
        'pedidos' => 'integer',
        'total' => 'decimal:2',
    ];
}
