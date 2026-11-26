<?php

namespace App\Models;

use App\Support\Dinero;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'id_factura',
        'id_usuario',
        'metodo',
        'monto',
        'efectivo_recibido',
        'cambio',
        'tarjeta_marca',
        'tarjeta_ultimos4',
        'referencia',
        'fecha_pago',
        'estado',
    ];

    protected $casts = [
        'id_factura' => 'integer',
        'id_usuario' => 'integer',
        'metodo' => 'string',
        'monto' => 'decimal:2',
        'efectivo_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
        'tarjeta_marca' => 'string',
        'tarjeta_ultimos4' => 'string',
        'referencia' => 'string',
        'fecha_pago' => 'datetime',
        'estado' => 'string',
    ];

    // Relaciones

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'id_factura' => [
                'required',
                'integer',
                'exists:facturas,id',
            ],

            'metodo' => [
                'required',
                'string',
                'in:efectivo,tarjeta',
            ],

            // Si no se indica, se paga todo el saldo. Un monto menor es
            // un abono.
            'monto' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'efectivo_recibido' => [
                'required_if:metodo,efectivo',
                'nullable',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'tarjeta_titular' => [
                'required_if:metodo,tarjeta',
                'nullable',
                'string',
                'min:3',
                'max:60',
                'regex:/^[\pL][\pL\s.\'\-]*$/u',
            ],

            'tarjeta_numero' => [
                'required_if:metodo,tarjeta',
                'nullable',
                'string',
                'max:25',
            ],

            'tarjeta_vencimiento' => [
                'required_if:metodo,tarjeta',
                'nullable',
                'string',
                'regex:/^(0[1-9]|1[0-2])\/\d{2}$/',
            ],

            'tarjeta_cvv' => [
                'required_if:metodo,tarjeta',
                'nullable',
                'digits_between:3,4',
            ],
        ];
    }

    // Consultas

    public static function mostrarPorFactura($idFactura)
    {
        return self::with('usuario:id,nombre,apellido')
            ->where('id_factura', $idFactura)
            ->orderBy('fecha_pago')
            ->orderBy('id')
            ->get();
    }

    // Tarjeta simulada

    // Algoritmo de Luhn (validación estándar del número de tarjeta).
    private static function luhn(string $numero): bool
    {
        $suma = 0;
        $alternar = false;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];

            if ($alternar) {
                $digito *= 2;

                if ($digito > 9) {
                    $digito -= 9;
                }
            }

            $suma += $digito;
            $alternar = !$alternar;
        }

        return $suma % 10 === 0;
    }

    private static function detectarMarca(string $numero): string
    {
        if (preg_match('/^4/', $numero)) {
            return 'Visa';
        }

        if (preg_match('/^(5[1-5]|222[1-9]|22[3-9]\d|2[3-6]\d\d|27[01]\d|2720)/', $numero)) {
            return 'Mastercard';
        }

        if (preg_match('/^3[47]/', $numero)) {
            return 'American Express';
        }

        return 'Otra';
    }

    // Valida número, vencimiento y CVV. Devuelve SOLO lo que se guarda:
    // la marca y los últimos 4 dígitos.
    private static function validarTarjeta(array $datos): array
    {
        $errores = [];

        $numero = preg_replace('/[\s-]+/', '', (string) ($datos['tarjeta_numero'] ?? ''));

        if (!preg_match('/^\d{13,19}$/', $numero)) {
            $errores['tarjeta_numero'] = ['El número de tarjeta debe tener entre 13 y 19 dígitos.'];
        } elseif (!self::luhn($numero)) {
            $errores['tarjeta_numero'] = ['El número de tarjeta no es válido.'];
        }

        $marca = self::detectarMarca($numero);

        try {
            $vence = Carbon::createFromFormat(
                '!m/y',
                trim((string) ($datos['tarjeta_vencimiento'] ?? ''))
            )->endOfMonth();

            if ($vence->lessThan(now())) {
                $errores['tarjeta_vencimiento'] = ['La tarjeta se encuentra vencida.'];
            } elseif ($vence->greaterThan(now()->addYears(10))) {
                $errores['tarjeta_vencimiento'] = ['La fecha de vencimiento no es válida.'];
            }
        } catch (\Throwable $e) {
            $errores['tarjeta_vencimiento'] = ['El vencimiento debe tener el formato MM/AA.'];
        }

        $longitudCvv = $marca === 'American Express' ? 4 : 3;

        if (!preg_match('/^\d{' . $longitudCvv . '}$/', (string) ($datos['tarjeta_cvv'] ?? ''))) {
            $errores['tarjeta_cvv'] = [
                "El CVV de una tarjeta {$marca} debe tener {$longitudCvv} dígitos.",
            ];
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        return [
            'marca' => $marca,
            'ultimos4' => substr($numero, -4),
        ];
    }

    // Registro de pagos

    // Registra un pago (o un abono) de una factura emitida. La tarjeta es
    // simulada: si los datos son válidos, siempre se aprueba. Cuando el
    // saldo llega a cero la factura queda pagada y el pedido, completado.
    public static function registrar(int $idFactura, array $datos, int $idUsuario)
    {
        $metodo = $datos['metodo'];

        // Se valida antes de abrir la transacción; el número completo
        // nunca se guarda ni sale de este método.
        $tarjeta = $metodo === 'tarjeta'
            ? self::validarTarjeta($datos)
            : null;

        return DB::transaction(function () use ($idFactura, $datos, $idUsuario, $metodo, $tarjeta) {

            $factura = Factura::whereKey($idFactura)
                ->lockForUpdate()
                ->first();

            if (!$factura) {
                throw ValidationException::withMessages([
                    'id_factura' => ['La factura seleccionada no existe.'],
                ]);
            }

            if ($factura->estado !== 'emitida') {
                throw ValidationException::withMessages([
                    'id_factura' => [
                        $factura->estado === 'pagada'
                            ? 'La factura ya se encuentra pagada.'
                            : 'La factura está anulada y no admite pagos.',
                    ],
                ]);
            }

            $pagado = self::where('id_factura', $factura->id)
                ->where('estado', 'aplicado')
                ->sum('monto');

            $saldo = Dinero::centavos($factura->total) - Dinero::centavos($pagado);

            // El pago siempre cubre el saldo completo: no hay abonos ni
            // montos libres.
            $monto = $saldo;

            if ($monto <= 0) {
                throw ValidationException::withMessages([
                    'id_factura' => ['La factura no tiene saldo pendiente.'],
                ]);
            }

            $campos = [
                'id_factura' => $factura->id,
                'id_usuario' => $idUsuario,
                'metodo' => $metodo,
                'monto' => Dinero::formato($monto),
                'fecha_pago' => now(),
                'estado' => 'aplicado',
            ];

            if ($metodo === 'efectivo') {

                $recibido = Dinero::centavos($datos['efectivo_recibido']);

                if ($recibido < $monto) {
                    throw ValidationException::withMessages([
                        'efectivo_recibido' => ['El efectivo recibido no cubre el monto a pagar.'],
                    ]);
                }

                $campos['efectivo_recibido'] = Dinero::formato($recibido);
                $campos['cambio'] = Dinero::formato($recibido - $monto);
            } else {
                $campos['tarjeta_marca'] = $tarjeta['marca'];
                $campos['tarjeta_ultimos4'] = $tarjeta['ultimos4'];
                $campos['referencia'] = 'SIM-' . Str::upper(Str::random(10));
            }

            $pago = self::create($campos);

            if ($saldo - $monto === 0) {
                self::liquidarFactura($factura, $idUsuario);
            }

            return $pago->load('usuario:id,nombre,apellido');
        });
    }

    // La factura quedó cubierta: se marca como pagada, el pedido se
    // completa, se acreditan los puntos ganados y se liberan las mesas.
    private static function liquidarFactura(Factura $factura, int $idUsuario): void
    {
        $factura->update(['estado' => 'pagada']);

        HistorialEstado::registrar(
            HistorialEstado::FACTURA,
            $factura->id,
            'emitida',
            'pagada',
            $idUsuario,
            'Factura pagada por completo'
        );

        $pedido = Pedido::whereKey($factura->id_pedido)
            ->lockForUpdate()
            ->first();

        if ($pedido && $pedido->estado === 'abierto') {

            $pedido->update(['estado' => 'completado']);

            HistorialEstado::registrar(
                HistorialEstado::PEDIDO,
                $pedido->id,
                'abierto',
                'completado',
                $idUsuario,
                'Factura ' . $factura->numero_factura . ' pagada'
            );

            Pedido::liberarMesas($pedido);
        }

        if ($factura->id_cliente && $factura->puntos_ganados > 0) {
            Cliente::whereKey($factura->id_cliente)
                ->increment('puntos', $factura->puntos_ganados);
        }
    }
}
