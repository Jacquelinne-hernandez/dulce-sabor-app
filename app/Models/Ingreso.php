<?php

namespace App\Models;

use App\Support\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

// Ingresos externos al giro del negocio (capital, inversiones, aportes...).
class Ingreso extends Model
{
    public const TIPOS = [
        'capital_inicial',
        'inversion',
        'aporte_socio',
        'prestamo',
        'donacion',
        'otros',
    ];

    protected $table = 'ingresos';

    protected $fillable = [
        'id_usuario',
        'tipo',
        'descripcion',
        'monto',
        'fecha_ingreso',
        'estado',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'tipo' => 'string',
        'descripcion' => 'string',
        'monto' => 'decimal:2',
        'fecha_ingreso' => 'date',
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

            // El tipo "otros" necesita explicar de dónde viene el dinero.
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

            'fecha_ingreso' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
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

    public static function mostrarFiltrados(
        ?string $desde = null,
        ?string $hasta = null,
        ?string $tipo = null
    ) {
        $consulta = self::with('usuario:id,nombre,apellido');

        if ($desde) {
            $consulta->whereDate('fecha_ingreso', '>=', $desde);
        }

        if ($hasta) {
            $consulta->whereDate('fecha_ingreso', '<=', $hasta);
        }

        if ($tipo) {
            $consulta->where('tipo', $tipo);
        }

        return $consulta
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->get();
    }

    public static function buscarIngresoXId($id)
    {
        return self::with('usuario:id,nombre,apellido')->find($id);
    }

    // Total de ingresos registrados (los anulados no cuentan), por tipo.
    public static function resumen(?string $desde = null, ?string $hasta = null): array
    {
        $ingresos = self::mostrarFiltrados($desde, $hasta)
            ->where('estado', 'registrado');

        $porTipo = [];

        foreach (self::TIPOS as $tipo) {
            $porTipo[$tipo] = Dinero::formato(
                $ingresos->where('tipo', $tipo)->sum(fn ($i) => Dinero::centavos($i->monto))
            );
        }

        return [
            'cantidad' => $ingresos->count(),
            'total' => Dinero::formato(
                $ingresos->sum(fn ($i) => Dinero::centavos($i->monto))
            ),
            'por_tipo' => $porTipo,
        ];
    }

    // Registro y edición

    // Solo puede haber un capital inicial vigente: con él arranca la caja.
    // Para meter más dinero se usan los demás tipos (inversión, aporte...).
    private static function validarCapitalUnico(string $tipo, ?int $exceptoId = null): void
    {
        if ($tipo !== Caja::CAPITAL_INICIAL) {
            return;
        }

        $existe = self::where('tipo', Caja::CAPITAL_INICIAL)
            ->where('estado', 'registrado')
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'tipo' => [
                    'Ya hay un capital inicial registrado. Para agregar más dinero '
                    . 'use Inversión, Aporte de socio u otro tipo de ingreso.',
                ],
            ]);
        }
    }

    public static function insertar(array $datos, int $idUsuario)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($datos, $idUsuario) {

            Caja::bloquear();

            self::validarCapitalUnico($datos['tipo']);

            $datos['id_usuario'] = $idUsuario;
            $datos['estado'] = 'registrado';

            return self::create($datos)->load('usuario:id,nombre,apellido');
        });
    }

    public static function actualizar($id, array $datos)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $datos) {

            Caja::bloquear();

            $ingreso = self::whereKey($id)->lockForUpdate()->first();

            if (!$ingreso) {
                return null;
            }

            if ($ingreso->estado === 'anulado') {
                throw ValidationException::withMessages([
                    'estado' => ['No se puede modificar un ingreso anulado.'],
                ]);
            }

            self::validarCapitalUnico($datos['tipo'] ?? $ingreso->tipo, $ingreso->id);

            // Si se reduce el monto, ese dinero no puede haberse gastado ya.
            $reduccion = Dinero::centavos($ingreso->monto)
                - Dinero::centavos($datos['monto'] ?? $ingreso->monto);

            if ($reduccion > 0) {
                Caja::exigirFondos($reduccion, 'monto', 'reducir este ingreso', false);
            }

            $ingreso->update($datos);

            return $ingreso->load('usuario:id,nombre,apellido');
        });
    }

    // Los ingresos no se borran: se anulan, para conservar el registro.
    public static function anular($id)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {

            Caja::bloquear();

            $ingreso = self::whereKey($id)->lockForUpdate()->first();

            if (!$ingreso) {
                return null;
            }

            if ($ingreso->estado === 'anulado') {
                throw ValidationException::withMessages([
                    'estado' => ['El ingreso ya se encuentra anulado.'],
                ]);
            }

            // El dinero de este ingreso pudo haberse usado ya: al anularlo, la
            // caja no puede quedar en negativo.
            Caja::exigirFondos(Dinero::centavos($ingreso->monto), 'estado', 'anular este ingreso', false);

            $ingreso->update(['estado' => 'anulado']);

            return $ingreso->load('usuario:id,nombre,apellido');
        });
    }
}
