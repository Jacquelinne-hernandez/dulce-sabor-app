<?php

namespace App\Models\Vistas;

// v_resumen_operativo: una sola fila con las cifras del momento.
class ResumenOperativo extends VistaSoloLectura
{
    protected $table = 'v_resumen_operativo';

    protected $casts = [
        'ordenes_pendientes' => 'integer',
        'ordenes_en_produccion' => 'integer',
        'ordenes_listas' => 'integer',
        'mesas_ocupadas' => 'integer',
        'mesas_disponibles' => 'integer',
        'facturas_por_cobrar' => 'integer',
        'clientes_activos' => 'integer',
    ];
}
