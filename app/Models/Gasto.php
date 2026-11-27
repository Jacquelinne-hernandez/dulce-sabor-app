<?php

namespace App\Models;

use App\Support\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

// Gastos adicionales del negocio (arrendamiento, luz, agua, internet...).
class Gasto extends Model
{
    public const TIPOS = [
        'arrendamiento',
        'luz',
        'agua',
        'internet',
        'salarios',
        'mantenimiento',
        'otros',
    ];

    protected $table = 'gastos';

    protected $fillable = [
        'id_usuario',
        'tipo',
        'descripcion',
        'monto',
        'fecha_gasto',
        'estado',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'tipo' => 'string',
        'descripcion' => 'string',
        'monto' => 'decimal:2',
        'fecha_gasto' => 'date',
        'estado' => 'string',
    ];

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'tipo' => [
                'required',
                'string',
                'in:' . implode(',', self::TIPOS),
            ],

            // El tipo "otros" necesita explicar en qué se gastó.
            'descripcion' => [
                'required_if:tipo,otros',
                'nullable',
                'string',
                'max:255',
            ],

            'monto' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'fecha_gasto' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    public static function validacionesFiltro(): array
    {
        return [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'tipo' => ['nullable', 'string', 'in:' . implode(',', self::TIPOS)],
        ];
    }

    // Consultas

    // Gastos del rango y tipo indicados (todos, si no se filtra).
    public static function mostrarFiltrados(
        ?string $desde = null,
        ?string $hasta = null,
        ?string $tipo = null
    ) {
        $consulta = self::with('usuario:id,nombre,apellido');

        if ($desde) {
            $consulta->whereDate('fecha_gasto', '>=', $desde);
        }

        if ($hasta) {
            $consulta->whereDate('fecha_gasto', '<=', $hasta);
        }

        if ($tipo) {
            $consulta->where('tipo', $tipo);
        }

        return $consulta
            ->orderByDesc('fecha_gasto')
            ->orderByDesc('id')
            ->get();
    }

    public static function buscarGastoXId($id)
    {
        return self::with('usuario:id,nombre,apellido')->find($id);
    }

    // Total de gastos registrados (los anulados no cuentan), por tipo.
    public static function resumen(?string $desde = null, ?string $hasta = null): array
    {
        $gastos = self::mostrarFiltrados($desde, $hasta)
            ->where('estado', 'registrado');

        $porTipo = [];

        foreach (self::TIPOS as $tipo) {
            $porTipo[$tipo] = Dinero::formato(
                $gastos->where('tipo', $tipo)->sum(fn ($g) => Dinero::centavos($g->monto))
            );
        }

        return [
            'cantidad' => $gastos->count(),
            'total' => Dinero::formato(
                $gastos->sum(fn ($g) => Dinero::centavos($g->monto))
            ),
            'por_tipo' => $porTipo,
        ];
    }

    // Registro y edición

    public static function insertar(array $datos, int $idUsuario)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($datos, $idUsuario) {

            // Sin capital inicial, o sin fondos, no se registran gastos.
            Caja::exigirFondos(Dinero::centavos($datos['monto']), 'monto', 'registrar gastos');

            $datos['id_usuario'] = $idUsuario;
            $datos['estado'] = 'registrado';

            return self::create($datos)->load('usuario:id,nombre,apellido');
        });
    }

    public static function actualizar($id, array $datos)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $datos) {

            Caja::bloquear();

            $gasto = self::whereKey($id)->lockForUpdate()->first();

            if (!$gasto) {
                return null;
            }

            if ($gasto->estado === 'anulado') {
                throw ValidationException::withMessages([
                    'estado' => ['No se puede modificar un gasto anulado.'],
                ]);
            }

            // Solo el aumento del gasto sale de la caja.
            $aumento = Dinero::centavos($datos['monto'] ?? $gasto->monto)
                - Dinero::centavos($gasto->monto);

            if ($aumento > 0) {
                Caja::exigirFondos($aumento, 'monto', 'aumentar este gasto');
            }

            $gasto->update($datos);

            return $gasto->load('usuario:id,nombre,apellido');
        });
    }

    // Los gastos no se borran: se anulan, para conservar el registro.
    public static function anular($id)
    {
        $gasto = self::find($id);

        if (!$gasto) {
            return null;
        }

        if ($gasto->estado === 'anulado') {
            throw ValidationException::withMessages([
                'estado' => ['El gasto ya se encuentra anulado.'],
            ]);
        }

        $gasto->update(['estado' => 'anulado']);

        return $gasto->load('usuario:id,nombre,apellido');
    }
}
