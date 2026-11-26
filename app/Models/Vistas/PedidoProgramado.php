<?php

namespace App\Models\Vistas;

// v_pedidos_programados: pedidos con fecha programada y orden en curso.
class PedidoProgramado extends VistaSoloLectura
{
    protected $table = 'v_pedidos_programados';

    protected $casts = [
        'id_pedido' => 'integer',
        'fecha_programada' => 'datetime',
        'total' => 'decimal:2',
    ];
}
