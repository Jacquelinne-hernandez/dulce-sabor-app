<?php

namespace App\Models;

use App\Support\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
 * Caja de la empresa: el dinero disponible.
 *
 *   disponible = ingresos externos (capital, inversiones...)
 *              + cobros de ventas (pagos aplicados)
 *              - gastos
 *              - pagos a proveedores (contado, abonos iniciales y abonos)
 *
 * Todo se calcula desde los movimientos registrados, así nunca se desajusta.
 * Los movimientos anulados no cuentan. Para operar, la empresa debe haber
 * registrado su capital inicial (Ingresos) y tener fondos suficientes.
 */
class Caja extends Model
{
    public const CAPITAL_INICIAL = 'capital_inicial';

    protected $table = 'caja';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Bloqueo
    |--------------------------------------------------------------------------
    */

    // Debe llamarse dentro de una transacción: mientras dure, ninguna otra
    // operación que gaste dinero puede avanzar.
    public static function bloquear(): void
    {
        if (self::query()->whereKey(1)->lockForUpdate()->value('id') === null) {
            self::query()->insertOrIgnore([[
                'id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);

            self::query()->whereKey(1)->lockForUpdate()->value('id');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Saldo
    |--------------------------------------------------------------------------
    */

    public static function ingresosCentavos(): int
    {
        return Dinero::centavos(
            Ingreso::where('estado', 'registrado')->sum('monto')
        );
    }

    public static function cobrosCentavos(): int
    {
        return Dinero::centavos(
            Pago::where('estado', 'aplicado')->sum('monto')
        );
    }

    public static function gastosCentavos(): int
    {
        return Dinero::centavos(
            Gasto::where('estado', 'registrado')->sum('monto')
        );
    }

    public static function pagosProveedoresCentavos(): int
    {
        return Dinero::centavos(PagoCompra::sum('monto'));
    }

    public static function saldoCentavos(): int
    {
        return self::ingresosCentavos()
            + self::cobrosCentavos()
            - self::gastosCentavos()
            - self::pagosProveedoresCentavos();
    }

    // La empresa arranca cuando registra su capital inicial.
    public static function iniciada(): bool
    {
        return Ingreso::where('tipo', self::CAPITAL_INICIAL)
            ->where('estado', 'registrado')
            ->exists();
    }

    public static function resumen(): array
    {
        $capital = Dinero::centavos(
            Ingreso::where('tipo', self::CAPITAL_INICIAL)
                ->where('estado', 'registrado')
                ->sum('monto')
        );

        $pendientes = Compra::where('estado_pago', '!=', 'pagada');

        $porPagar = Dinero::centavos((clone $pendientes)->sum('total'))
            - Dinero::centavos((clone $pendientes)->sum('monto_pagado'));

        return [
            'iniciada' => self::iniciada(),
            'disponible' => Dinero::formato(self::saldoCentavos()),
            'capital_inicial' => Dinero::formato($capital),
            'ingresos' => Dinero::formato(self::ingresosCentavos()),
            'cobros' => Dinero::formato(self::cobrosCentavos()),
            'gastos' => Dinero::formato(self::gastosCentavos()),
            'pagos_proveedores' => Dinero::formato(self::pagosProveedoresCentavos()),
            'por_pagar' => Dinero::formato(max(0, $porPagar)),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas para operar
    |--------------------------------------------------------------------------
    */

    // Sin capital inicial no se puede gastar ni comprar.
    public static function exigirCapital(string $campo, string $accion): void
    {
        if (!self::iniciada()) {
            throw ValidationException::withMessages([
                $campo => [
                    'La empresa aún no tiene capital inicial. Regístrelo en Ingresos '
                    . '(tipo «Capital inicial») antes de ' . $accion . '.',
                ],
            ]);
        }
    }

    // Verifica que haya dinero para sacar $centavos de la caja. Bloquea la
    // caja: debe usarse dentro de una transacción.
    public static function exigirFondos(
        int $centavos,
        string $campo,
        string $accion,
        bool $exigirCapital = true
    ): void {
        self::bloquear();

        if ($exigirCapital) {
            self::exigirCapital($campo, $accion);
        }

        if ($centavos <= 0) {
            return;
        }

        $disponible = self::saldoCentavos();

        if ($centavos > $disponible) {
            throw ValidationException::withMessages([
                $campo => [
                    'No hay fondos suficientes para ' . $accion . ': se requieren $'
                    . Dinero::formato($centavos) . ' y solo hay $'
                    . Dinero::formato(max(0, $disponible)) . ' disponibles.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Estado de cuenta
    |--------------------------------------------------------------------------
    */

    // Todos los movimientos vigentes en una sola consulta, del más reciente
    // al más antiguo: origen, ref_id, momento, fecha, tipo, descripcion,
    // entrada y salida.
    private static function consultaMovimientos()
    {
        $ingresos = Ingreso::query()
            ->where('estado', 'registrado')
            ->selectRaw("'ingreso' as origen, id as ref_id, created_at as momento, fecha_ingreso as fecha, tipo, descripcion, monto as entrada, 0 as salida");

        $gastos = Gasto::query()
            ->where('estado', 'registrado')
            ->selectRaw("'gasto' as origen, id as ref_id, created_at as momento, fecha_gasto as fecha, tipo, descripcion, 0 as entrada, monto as salida");

        $cobros = Pago::query()
            ->join('facturas', 'facturas.id', '=', 'pagos.id_factura')
            ->where('pagos.estado', 'aplicado')
            ->selectRaw("'cobro' as origen, pagos.id as ref_id, pagos.fecha_pago as momento, DATE(pagos.fecha_pago) as fecha, pagos.metodo as tipo, facturas.numero_factura as descripcion, pagos.monto as entrada, 0 as salida");

        $pagos = PagoCompra::query()
            ->join('compras', 'compras.id', '=', 'pagos_compras.id_compra')
            ->join('proveedores', 'proveedores.id', '=', 'compras.id_proveedor')
            ->selectRaw("'pago_compra' as origen, pagos_compras.id as ref_id, pagos_compras.fecha_pago as momento, DATE(pagos_compras.fecha_pago) as fecha, pagos_compras.observaciones as tipo, proveedores.nombre as descripcion, 0 as entrada, pagos_compras.monto as salida");

        return $ingresos->toBase()
            ->unionAll($gastos->toBase())
            ->unionAll($cobros->toBase())
            ->unionAll($pagos->toBase())
            ->orderByDesc('momento')
            ->orderByDesc('origen')
            ->orderByDesc('ref_id');
    }

    // Una página del estado de cuenta, con el saldo que dejó cada movimiento.
    public static function movimientos(int $pagina = 1, int $porPagina = 25): array
    {
        $porPagina = max(1, min($porPagina, 100));
        $paginador = self::consultaMovimientos()->paginate($porPagina, ['*'], 'page', max(1, $pagina));

        // Saldo tras el movimiento más reciente de la página = saldo actual
        // menos lo que movieron los registros más nuevos que esta página.
        $anteriores = ($paginador->currentPage() - 1) * $porPagina;
        $netoNuevos = 0;

        if ($anteriores > 0) {
            $neto = DB::query()
                ->fromSub(self::consultaMovimientos()->limit($anteriores), 'nuevos')
                ->selectRaw('COALESCE(SUM(entrada), 0) - COALESCE(SUM(salida), 0) as neto')
                ->value('neto');

            $netoNuevos = Dinero::centavos($neto);
        }

        $saldo = self::saldoCentavos() - $netoNuevos;
        $filas = [];

        foreach ($paginador->items() as $m) {
            $entrada = Dinero::centavos($m->entrada);
            $salida = Dinero::centavos($m->salida);

            $filas[] = [
                'origen' => $m->origen,
                'ref_id' => (int) $m->ref_id,
                'momento' => $m->momento,
                'fecha' => $m->fecha,
                'tipo' => $m->tipo,
                'descripcion' => $m->descripcion,
                'entrada' => $entrada > 0 ? Dinero::formato($entrada) : null,
                'salida' => $salida > 0 ? Dinero::formato($salida) : null,
                'saldo' => Dinero::formato($saldo),
            ];

            $saldo -= ($entrada - $salida);
        }

        return [
            'movimientos' => $filas,
            'paginacion' => [
                'pagina' => $paginador->currentPage(),
                'por_pagina' => $paginador->perPage(),
                'total' => $paginador->total(),
                'ultima' => $paginador->lastPage(),
                'desde' => $paginador->firstItem() ?? 0,
                'hasta' => $paginador->lastItem() ?? 0,
            ],
        ];
    }
}
