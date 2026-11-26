<?php

namespace App\Models\Vistas;

// v_stock_bajo: productos con existencias en el mínimo o por debajo.
class StockBajo extends VistaSoloLectura
{
    protected $table = 'v_stock_bajo';

    protected $casts = [
        'id_producto' => 'integer',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
    ];
}
