<?php

namespace App\Models\Vistas;

// v_cobros_diarios: dinero cobrado por día y método de pago.
class CobroDiario extends VistaSoloLectura
{
    protected $table = 'v_cobros_diarios';

    protected $casts = [
        'fecha' => 'date',
        'metodo' => 'string',
        'pagos' => 'integer',
        'monto' => 'decimal:2',
    ];
}
