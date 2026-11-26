<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleReserva extends Model
{
    protected $table = 'detalle_reservas';

    protected $fillable = [
        'id_reserva',
        'id_mesa',
        'tarifa_hora',
        'horas',
        'subtotal',
    ];

    protected $casts = [
        'id_reserva' => 'integer',
        'id_mesa' => 'integer',
        'tarifa_hora' => 'decimal:2',
        'horas' => 'float',
        'subtotal' => 'decimal:2',
    ];

    // Relaciones

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'id_mesa');
    }
}
