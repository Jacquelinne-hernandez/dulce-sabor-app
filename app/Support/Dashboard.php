<?php

namespace App\Support;

use App\Models\Factura;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\Vistas\CobroDiario;
use App\Models\Vistas\CompraDiaria;
use App\Models\Vistas\DeudaProveedor;
use App\Models\Vistas\FacturaUtilidad;
use App\Models\Vistas\GastoDiario;
use App\Models\Vistas\PagoCompraDiario;
use App\Models\Vistas\PedidoDiario;
use App\Models\Vistas\PedidoProgramado;
use App\Models\Vistas\ProductoVendidoDiario;
use App\Models\Vistas\ReservaPendiente;
use App\Models\Vistas\ResumenOperativo;
use App\Models\Vistas\StockBajo;
use App\Models\Vistas\VentaDiaria;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Datos de los dashboards. Todo sale de las vistas SQL (modelos de solo
// lectura); aquí solo se suma, se agrupa y se da formato.
class Dashboard
{
    // Rango máximo que admite el dashboard del administrador.
    public const DIAS_MAXIMOS = 366;

    /*
    |--------------------------------------------------------------------------
    | Utilidades
    |--------------------------------------------------------------------------
    */

    // Suma una columna decimal de una colección, en centavos.
    private static function suma($coleccion, string $campo): int
    {
        return (int) $coleccion->sum(fn ($fila) => Dinero::centavos($fila->{$campo}));
    }

    private static function formato(int $centavos): string
    {
        return Dinero::formato($centavos);
    }

    // Normaliza el rango pedido: por defecto, del primer día del mes a hoy.
    // Fechas inválidas o invertidas vuelven al valor por defecto, y el
    // rango se limita a DIAS_MAXIMOS.
    public static function rango(?string $desde = null, ?string $hasta = null): array
    {
        $hoy = Carbon::today();

        try {
            $fin = $hasta ? Carbon::parse($hasta)->startOfDay() : $hoy->copy();
        } catch (\Throwable $e) {
            $fin = $hoy->copy();
        }

        try {
            $inicio = $desde ? Carbon::parse($desde)->startOfDay() : $hoy->copy()->startOfMonth();
        } catch (\Throwable $e) {
            $inicio = $hoy->copy()->startOfMonth();
        }

        if ($inicio->greaterThan($fin)) {
            $inicio = $fin->copy()->startOfMonth();
        }

        if ($inicio->diffInDays($fin) > self::DIAS_MAXIMOS - 1) {
            $inicio = $fin->copy()->subDays(self::DIAS_MAXIMOS - 1);
        }

        return [$inicio->toDateString(), $fin->toDateString()];
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard del operador
    |--------------------------------------------------------------------------
    */

    public static function operador(): array
    {
        $hoy = Carbon::today();
        $fecha = $hoy->toDateString();

        $ventas = VentaDiaria::where('fecha', $fecha)->first();

        $pedidos = PedidoDiario::where('fecha', $fecha)->get();

        $cobros = CobroDiario::where('fecha', $fecha)->get();

        // Sin costo: el operador solo ve unidades e ingresos.
        $masVendidos = ProductoVendidoDiario::where('fecha', $fecha)
            ->orderByDesc('unidades')
            ->limit(5)
            ->get(['id_producto', 'producto', 'unidades', 'ingresos']);

        $programados = PedidoProgramado::where('fecha_programada', '>=', $hoy)
            ->orderBy('fecha_programada')
            ->limit(8)
            ->get();

        $reservasHoy = ReservaPendiente::where('fecha', $fecha)
            ->orderBy('hora_inicio')
            ->get();

        return [
            'fecha' => $fecha,

            'ventas' => [
                'facturas' => (int) ($ventas->facturas ?? 0),
                'total' => self::formato(Dinero::centavos($ventas->total_ventas ?? 0)),
                'descuentos' => self::formato(Dinero::centavos($ventas->descuentos ?? 0)),
                'iva' => self::formato(Dinero::centavos($ventas->iva ?? 0)),
            ],

            'pedidos' => [
                'total' => (int) $pedidos->sum('pedidos'),
                'por_tipo' => [
                    'local' => (int) $pedidos->where('tipo', 'local')->sum('pedidos'),
                    'llevar' => (int) $pedidos->where('tipo', 'llevar')->sum('pedidos'),
                    'reserva' => (int) $pedidos->where('tipo', 'reserva')->sum('pedidos'),
                ],
            ],

            'cobros' => [
                'total' => self::formato(self::suma($cobros, 'monto')),
                'efectivo' => self::formato(self::suma($cobros->where('metodo', 'efectivo'), 'monto')),
                'tarjeta' => self::formato(self::suma($cobros->where('metodo', 'tarjeta'), 'monto')),
            ],

            'mas_vendidos' => $masVendidos,
            'operativo' => self::operativo(),
            'programados' => $programados,
            'reservas_hoy' => $reservasHoy,
            'stock_bajo' => StockBajo::orderBy('stock')->get(),
        ];
    }

    // Cifras del momento (si la vista no tiene filas, todo en cero).
    private static function operativo(): array
    {
        $fila = ResumenOperativo::first();

        return [
            'ordenes_pendientes' => (int) ($fila->ordenes_pendientes ?? 0),
            'ordenes_en_produccion' => (int) ($fila->ordenes_en_produccion ?? 0),
            'ordenes_listas' => (int) ($fila->ordenes_listas ?? 0),
            'mesas_ocupadas' => (int) ($fila->mesas_ocupadas ?? 0),
            'mesas_disponibles' => (int) ($fila->mesas_disponibles ?? 0),
            'facturas_por_cobrar' => (int) ($fila->facturas_por_cobrar ?? 0),
            'clientes_activos' => (int) ($fila->clientes_activos ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard (y reporte) del administrador
    |--------------------------------------------------------------------------
    */

    public static function admin(?string $desde = null, ?string $hasta = null): array
    {
        [$desde, $hasta] = self::rango($desde, $hasta);

        // ---- Ventas y utilidad bruta -----------------------------------

        $facturas = FacturaUtilidad::whereBetween('fecha', [$desde, $hasta])->get();

        $ventas = self::suma($facturas, 'total');
        $iva = self::suma($facturas, 'iva');
        $base = self::suma($facturas, 'base_imponible');
        $costo = self::suma($facturas, 'costo_ventas');
        $utilidadBruta = $base - $costo;

        // ---- Gastos -----------------------------------------------------

        $gastos = GastoDiario::whereBetween('fecha', [$desde, $hasta])->get();
        $gastosTotal = self::suma($gastos, 'monto');

        $gastosPorTipo = [];
        foreach (Gasto::TIPOS as $tipo) {
            $gastosPorTipo[$tipo] = self::formato(
                self::suma($gastos->where('tipo', $tipo), 'monto')
            );
        }

        // ---- Compras y pagos a proveedores ------------------------------

        $compras = CompraDiaria::whereBetween('fecha', [$desde, $hasta])->get();
        $pagosProveedores = PagoCompraDiario::whereBetween('fecha', [$desde, $hasta])->get();

        $totalComprado = self::suma($compras, 'total_comprado');
        $totalCredito = self::suma($compras, 'total_credito');
        $pagadoProveedores = self::suma($pagosProveedores, 'monto');

        // ---- Dinero cobrado y flujo de caja -----------------------------

        $cobros = CobroDiario::whereBetween('fecha', [$desde, $hasta])->get();

        $cobrado = self::suma($cobros, 'monto');
        $cobradoEfectivo = self::suma($cobros->where('metodo', 'efectivo'), 'monto');
        $cobradoTarjeta = self::suma($cobros->where('metodo', 'tarjeta'), 'monto');

        // ---- Ingresos externos (capital, inversiones, aportes) ----------
        // No son ventas: no cambian la utilidad, solo el dinero en caja.

        $ingresos = Ingreso::where('estado', 'registrado')
            ->whereBetween('fecha_ingreso', [$desde, $hasta])
            ->get();

        $ingresosTotal = self::suma($ingresos, 'monto');

        $ingresosPorTipo = [];
        foreach (Ingreso::TIPOS as $tipo) {
            $ingresosPorTipo[$tipo] = self::formato(
                self::suma($ingresos->where('tipo', $tipo), 'monto')
            );
        }

        // ---- Serie diaria: ventas vs compras vs gastos ------------------

        $ventasPorDia = $facturas->groupBy(fn ($f) => $f->fecha->toDateString());
        $comprasPorDia = $compras->keyBy(fn ($c) => $c->fecha->toDateString());
        $gastosPorDia = $gastos->groupBy(fn ($g) => $g->fecha->toDateString());

        $serie = [];

        foreach (CarbonPeriod::create($desde, $hasta) as $dia) {
            $clave = $dia->toDateString();

            $serie[] = [
                'fecha' => $clave,
                'ventas' => self::formato(self::suma($ventasPorDia->get($clave, collect()), 'total')),
                'compras' => self::formato(
                    Dinero::centavos(optional($comprasPorDia->get($clave))->total_comprado ?? 0)
                ),
                'gastos' => self::formato(self::suma($gastosPorDia->get($clave, collect()), 'monto')),
            ];
        }

        // ---- Productos --------------------------------------------------

        $porProducto = ProductoVendidoDiario::whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->groupBy('id_producto')
            ->map(function ($filas) {
                $ingresos = self::suma($filas, 'ingresos');
                $costoProducto = self::suma($filas, 'costo');

                // Los precios incluyen IVA: la utilidad se mide sin él.
                $sinIva = (int) round($ingresos * 100 / (100 + Factura::IVA_PORCENTAJE));

                return [
                    'id_producto' => $filas->first()->id_producto,
                    'producto' => $filas->first()->producto,
                    'unidades' => (int) $filas->sum('unidades'),
                    'ingresos_centavos' => $ingresos,
                    'utilidad_centavos' => $sinIva - $costoProducto,
                ];
            })
            ->values();

        $formatearProducto = fn ($p) => [
            'id_producto' => $p['id_producto'],
            'producto' => $p['producto'],
            'unidades' => $p['unidades'],
            'ingresos' => self::formato($p['ingresos_centavos']),
            'utilidad' => self::formato($p['utilidad_centavos']),
        ];

        // ---- Deudas con proveedores (a hoy) -----------------------------

        $hoy = Carbon::today();

        $deudas = DeudaProveedor::orderBy('fecha_vencimiento')->get()
            ->map(function ($d) use ($hoy) {
                $d->vencida = $d->fecha_vencimiento !== null
                    && $d->fecha_vencimiento->lt($hoy);

                return $d;
            });

        $deudasPorProveedor = $deudas
            ->groupBy('id_proveedor')
            ->map(fn ($filas) => [
                'id_proveedor' => $filas->first()->id_proveedor,
                'proveedor' => $filas->first()->proveedor,
                'compras' => $filas->count(),
                'saldo_centavos' => self::suma($filas, 'saldo'),
                'vencido_centavos' => self::suma($filas->where('vencida', true), 'saldo'),
            ])
            ->sortByDesc('saldo_centavos')
            ->values()
            ->map(fn ($f) => [
                'id_proveedor' => $f['id_proveedor'],
                'proveedor' => $f['proveedor'],
                'compras' => $f['compras'],
                'saldo' => self::formato($f['saldo_centavos']),
                'vencido' => self::formato($f['vencido_centavos']),
            ]);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'generado' => now()->format('d/m/Y H:i'),

            // Estado de resultados del período.
            'resultados' => [
                'facturas' => $facturas->count(),
                'ventas' => self::formato($ventas),
                'iva' => self::formato($iva),
                'ventas_netas' => self::formato($base),
                'costo_ventas' => self::formato($costo),
                'utilidad_bruta' => self::formato($utilidadBruta),
                'gastos' => self::formato($gastosTotal),
                'utilidad_neta' => self::formato($utilidadBruta - $gastosTotal),
            ],

            'gastos_por_tipo' => $gastosPorTipo,

            'ingresos_por_tipo' => $ingresosPorTipo,

            'compras' => [
                'cantidad' => (int) $compras->sum('compras'),
                'total_comprado' => self::formato($totalComprado),
                'a_credito' => self::formato($totalCredito),
                'pagado_proveedores' => self::formato($pagadoProveedores),
            ],

            // Dinero que realmente entró y salió en el período.
            'flujo_caja' => [
                'cobrado' => self::formato($cobrado),
                'cobrado_efectivo' => self::formato($cobradoEfectivo),
                'cobrado_tarjeta' => self::formato($cobradoTarjeta),
                'pagado_proveedores' => self::formato($pagadoProveedores),
                'gastos' => self::formato($gastosTotal),
                'ingresos_externos' => self::formato($ingresosTotal),
                'neto' => self::formato($cobrado + $ingresosTotal - $pagadoProveedores - $gastosTotal),
            ],

            // Deuda actual (no depende del rango).
            'deudas' => [
                'compras_pendientes' => $deudas->count(),
                'compras_vencidas' => $deudas->where('vencida', true)->count(),
                'total_adeudado' => self::formato(self::suma($deudas, 'saldo')),
                'total_vencido' => self::formato(self::suma($deudas->where('vencida', true), 'saldo')),
                'por_proveedor' => $deudasPorProveedor,
                'proximas' => $deudas->take(8)->map(fn ($d) => [
                    'id_compra' => $d->id_compra,
                    'proveedor' => $d->proveedor,
                    'fecha_vencimiento' => optional($d->fecha_vencimiento)->toDateString(),
                    'saldo' => self::formato(Dinero::centavos($d->saldo)),
                    'vencida' => $d->vencida,
                ])->values(),
            ],

            'serie_diaria' => $serie,

            'mas_vendidos' => $porProducto
                ->sortByDesc('unidades')->take(10)->values()->map($formatearProducto),

            'mas_rentables' => $porProducto
                ->sortByDesc('utilidad_centavos')->take(10)->values()->map($formatearProducto),

            // Cifras del momento y alertas de inventario.
            'operativo' => self::operativo(),
            'stock_bajo' => StockBajo::orderBy('stock')->get(),
        ];
    }
}
