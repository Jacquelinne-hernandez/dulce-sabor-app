<?php

namespace App\Models\Vistas;

// v_productos_vendidos_diario: unidades e ingresos por producto y día.
class ProductoVendidoDiario extends VistaSoloLectura
{
    protected $table = 'v_productos_vendidos_diario';

    protected $casts = [
        'fecha' => 'date',
        'id_producto' => 'integer',
        'producto' => 'string',
        'unidades' => 'integer',
        'ingresos' => 'decimal:2',
        'costo' => 'decimal:2',
    ];
}
