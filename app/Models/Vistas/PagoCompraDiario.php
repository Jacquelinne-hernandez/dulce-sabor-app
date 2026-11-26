<?php

namespace App\Models\Vistas;

// v_pagos_compras_diarios: pagos a proveedores por día.
class PagoCompraDiario extends VistaSoloLectura
{
    protected $table = 'v_pagos_compras_diarios';

    protected $casts = [
        'fecha' => 'date',
        'pagos' => 'integer',
        'monto' => 'decimal:2',
    ];
}
