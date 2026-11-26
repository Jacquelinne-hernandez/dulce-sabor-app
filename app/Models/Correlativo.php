<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Correlativo extends Model
{
    public const CLIENTE = 'CLIENTE';
    public const FACTURA = 'FACTURA';
    public const ORDEN = 'ORDEN';

    protected $table = 'correlativos';

    protected $fillable = [
        'tipo',
        'prefijo',
        'longitud',
        'ultimo_numero',
    ];

    protected $casts = [
        'tipo' => 'string',
        'prefijo' => 'string',
        'longitud' => 'integer',
        'ultimo_numero' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Numeración secuencial
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve el siguiente número del tipo indicado (por ejemplo
     * FAC-000001). Bloquea la fila para que dos usuarios que facturen
     * al mismo tiempo nunca reciban el mismo número.
     */
    public static function siguiente(string $tipo): string
    {
        return DB::transaction(function () use ($tipo) {

            $correlativo = self::where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            if (!$correlativo) {
                throw ValidationException::withMessages([
                    'correlativo' => [
                        "No existe el correlativo {$tipo}. Ejecute los seeders.",
                    ],
                ]);
            }

            $correlativo->ultimo_numero = $correlativo->ultimo_numero + 1;
            $correlativo->save();

            return $correlativo->prefijo . str_pad(
                (string) $correlativo->ultimo_numero,
                $correlativo->longitud,
                '0',
                STR_PAD_LEFT
            );
        });
    }
}
