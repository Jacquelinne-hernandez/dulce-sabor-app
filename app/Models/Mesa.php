<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Mesa extends Model
{
    use HasFactory;

    protected $table = 'mesas';

    protected $fillable = [
        'numero',
        'capacidad',
        'tarifa_hora',
        'estado'
    ];

    protected $casts = [
        'numero' => 'integer',
        'capacidad' => 'integer',
        'tarifa_hora' => 'decimal:2',
        'estado' => 'string'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Una mesa puede estar en varias reservas (y una reserva en varias
    // mesas): la relación pasa por la tabla detalle_reservas.
    public function reservas(): BelongsToMany
    {
        return $this->belongsToMany(
            Reserva::class,
            'detalle_reservas',
            'id_mesa',
            'id_reserva'
        )
            ->withPivot(['tarifa_hora', 'horas', 'subtotal'])
            ->withTimestamps();
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_mesa');
    }


    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones($id = null): array
    {
        return [
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('mesas', 'numero')->ignore($id)
            ],

            'capacidad' => [
                'required',
                'integer',
                'min:1'
            ],

            // Tarifa que se cobra por hora cuando la mesa se reserva.
            'tarifa_hora' => [
                'required',
                'numeric',
                'min:0',
                'max:9999.99'
            ]
        ];
    }

    public static function reglasEstado(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in([
                    'disponible',
                    'reservada',
                    'ocupada',
                    'mantenimiento'
                ])
            ]
        ];
    }

    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas y CRUD
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::orderBy('numero')->get();
    }

    public static function buscarMesaXId($id)
    {
        return self::find($id);
    }

    public static function insertar($datos)
    {
        return self::create($datos);
    }

    public static function actualizar($id, $datos)
    {
        $mesa = self::find($id);

        if (!$mesa) {
            return null;
        }

        $mesa->update($datos);

        return $mesa;
    }

    /*
    |--------------------------------------------------------------------------
    | Gestión de estados
    |--------------------------------------------------------------------------
    */

    public static function mostrarPorEstado($estado)
    {
        Validator::make(
            ['estado' => $estado],
            static::reglasEstado()
        )->validate();

        return self::where('estado', $estado)
            ->orderBy('numero')
            ->get();
    }

    public static function cambiarEstado($id, $estado)
    {
        Validator::make(
            ['estado' => $estado],
            static::reglasEstado()
        )->validate();

        $mesa = self::find($id);

        if (!$mesa) {
            return null;
        }

        $mesa->update([
            'estado' => $estado
        ]);

        return $mesa;
    }
}
