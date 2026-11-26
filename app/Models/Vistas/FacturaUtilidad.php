<?php

namespace App\Models\Vistas;

// v_facturas_utilidad: utilidad por factura. Contiene costos: solo la
// usa el dashboard del administrador.
class FacturaUtilidad extends VistaSoloLectura
{
    protected $table = 'v_facturas_utilidad';

    protected $casts = [
        'id_factura' => 'integer',
        'fecha' => 'date',
        'estado' => 'string',
        'total' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'iva' => 'decimal:2',
        'cargo_mesas' => 'decimal:2',
        'costo_ventas' => 'decimal:2',
        'utilidad_bruta' => 'decimal:2',
    ];
}
