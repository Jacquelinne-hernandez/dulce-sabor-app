<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstado extends Model
{
    // Entidades que registran su trazabilidad.
    public const PEDIDO = 'PEDIDO';
    public const ORDEN = 'ORDEN';
    public const FACTURA = 'FACTURA';
    public const RESERVA = 'RESERVA';

    protected $table = 'historial_estados';

    protected $fillable = [
        'entidad',
        'id_entidad',
        'estado_anterior',
        'estado_nuevo',
        'id_usuario',
        'comentario',
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'entidad' => 'string',
        'id_entidad' => 'integer',
        'estado_anterior' => 'string',
        'estado_nuevo' => 'string',
        'id_usuario' => 'integer',
        'comentario' => 'string',
        'created_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /*
    |--------------------------------------------------------------------------
    | Registro y consulta
    |--------------------------------------------------------------------------
    */

    // Anota un cambio de estado (el estado anterior es null al crear).
    public static function registrar(
        string $entidad,
        int $idEntidad,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?int $idUsuario = null,
        ?string $comentario = null
    ): self {
        return static::create([
            'entidad' => $entidad,
            'id_entidad' => $idEntidad,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'id_usuario' => $idUsuario,
            'comentario' => $comentario,
        ]);
    }

    // Línea de tiempo de un registro, del cambio más antiguo al más reciente.
    public static function mostrarPorEntidad(string $entidad, int $idEntidad)
    {
        return self::with('usuario:id,nombre,apellido')
            ->where('entidad', $entidad)
            ->where('id_entidad', $idEntidad)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
