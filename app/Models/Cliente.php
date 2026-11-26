<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'id_estado',
        'nombre',
        'telefono',
        'correo',
        'fecha_registro',
        'puntos'
    ];

    protected $casts = [
        'id_estado' => 'integer',
        'nombre' => 'string',
        'telefono' => 'string',
        'correo' => 'string',
        'fecha_registro' => 'date',
        'puntos' => 'integer'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_cliente');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_cliente');
    }



    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones($id = null): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100'
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:20'
            ],

            'correo' => [
                'nullable',
                'string',
                'email',
                'max:100',
                Rule::unique('clientes', 'correo')->ignore($id)
            ]
        ];
    }

    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id),
            [
                'nombre.required' => 'El nombre del cliente es obligatorio.',
                'nombre.string' => 'El nombre debe ser una cadena de texto.',
                'nombre.max' => 'El nombre no debe superar los 100 caracteres.',

                'telefono.string' => 'El teléfono debe ser una cadena de texto.',
                'telefono.max' => 'El teléfono no debe superar los 20 caracteres.',

                'correo.string' => 'El correo debe ser una cadena de texto.',
                'correo.email' => 'El correo electrónico no tiene un formato válido.',
                'correo.max' => 'El correo no debe superar los 100 caracteres.',
                'correo.unique' => 'El correo electrónico ya está registrado.'
            ]
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas y CRUD
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos()
    {
        return self::with('estado')
            ->where('id_estado', '!=', Estado::ELIMINADO)
            ->orderBy('nombre')
            ->get();
    }

    public static function buscarClienteXId($id)
    {
        return self::with('estado')
            ->where('id_estado', '!=', Estado::ELIMINADO)
            ->find($id);
    }

    public static function insertar($datos)
    {
        // Valores iniciales de todo cliente nuevo.
        $datos['id_estado'] = Estado::ACTIVO;
        $datos['fecha_registro'] = now()->toDateString();
        $datos['puntos'] = 0;

        return self::create($datos)->load('estado');
    }

    public static function actualizar($id, $datos)
    {
        $cliente = self::where('id_estado', '!=', Estado::ELIMINADO)->find($id);

        if (!$cliente) {
            return null;
        }

        $cliente->update($datos);

        return $cliente;
    }

    public static function eliminar($id)
    {
        $cliente = self::where('id_estado', '!=', Estado::ELIMINADO)->find($id);

        if (!$cliente) {
            return false;
        }

        $cliente->update([
            'id_estado' => Estado::ELIMINADO
        ]);

        return true;
    }
}
