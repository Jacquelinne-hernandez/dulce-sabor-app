<?php

namespace App\Models\Vistas;

// v_deudas_proveedores: compras con saldo pendiente.
class DeudaProveedor extends VistaSoloLectura
{
    protected $table = 'v_deudas_proveedores';

    protected $casts = [
        'id_compra' => 'integer',
        'id_proveedor' => 'integer',
        'fecha_compra' => 'datetime',
        'fecha_vencimiento' => 'date',
        'total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo' => 'decimal:2',
        'estado_pago' => 'string',
    ];
}
