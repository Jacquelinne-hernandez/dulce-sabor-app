<?php

namespace App\Models\Vistas;

// v_ventas_diarias: ventas facturadas por día.
class VentaDiaria extends VistaSoloLectura
{
    protected $table = 'v_ventas_diarias';

    protected $casts = [
        'fecha' => 'date',
        'facturas' => 'integer',
        'total_ventas' => 'decimal:2',
        'descuentos' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'iva' => 'decimal:2',
    ];
}
