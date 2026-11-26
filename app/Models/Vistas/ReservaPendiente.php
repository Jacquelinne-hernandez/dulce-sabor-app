<?php

namespace App\Models\Vistas;

// v_reservas_pendientes: reservas pendientes o confirmadas con sus mesas.
class ReservaPendiente extends VistaSoloLectura
{
    protected $table = 'v_reservas_pendientes';

    protected $casts = [
        'id_reserva' => 'integer',
        'fecha' => 'date',
        'personas' => 'integer',
    ];
}
