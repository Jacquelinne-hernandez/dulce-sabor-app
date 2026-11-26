<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'estado'
    ];

    protected $casts = [
        'numero' => 'integer',
        'capacidad' => 'integer',
        'estado' => 'string'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_mesa');
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
