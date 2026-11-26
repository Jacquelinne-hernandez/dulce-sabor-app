<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Support\Dashboard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentoController extends Controller
{
    // Factura completa (hoja carta).
    public function factura(Request $request, $id)
    {
        $factura = $this->cargarFactura($id);

        return $this->generar(
            'pdf.factura',
            ['factura' => $factura],
            'letter',
            'Factura-' . $factura->numero_factura . '.pdf',
            $request
        );
    }

    // Ticket de 80 mm para quien no necesita la factura completa.
    public function ticket(Request $request, $id)
    {
        $factura = $this->cargarFactura($id);

        // El ticket es angosto y su alto depende de cuántas líneas tenga.
        $lineas = $factura->detalles->count()
            + $factura->pagos->where('estado', 'aplicado')->count()
            + ($factura->pedido->reserva?->mesas->count() ?? 0);

        $alto = 330 + ($lineas * 28);

        return $this->generar(
            'pdf.ticket',
            ['factura' => $factura],
            [0, 0, 226.77, $alto],
            'Ticket-' . $factura->numero_factura . '.pdf',
            $request
        );
    }

    // Resumen financiero del período (solo administrador).
    public function reporteFinanciero(Request $request)
    {
        $datos = Dashboard::admin(
            $request->query('desde'),
            $request->query('hasta')
        );

        return $this->generar(
            'pdf.reporte_financiero',
            [
                'datos' => $datos,
                'usuario' => auth()->user(),
            ],
            'letter',
            'Resumen-financiero-' . $datos['desde'] . '_' . $datos['hasta'] . '.pdf',
            $request
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Apoyo
    |--------------------------------------------------------------------------
    */

    private function cargarFactura($id): Factura
    {
        $factura = Factura::buscarFacturaXId($id);

        abort_if(!$factura, 404, 'La factura solicitada no existe.');

        return $factura->load(['pedido.mesa', 'pedido.reserva.mesas', 'pedido.orden']);
    }

    // Logo como imagen incrustada (así el PDF no depende de rutas ni de
    // permisos de lectura). Devuelve null si no hay logo.
    private function logo(): ?string
    {
        $carpeta = public_path('images');

        $candidatos = [
            'logo_dulceSabor.svg' => 'image/svg+xml',
            'logo._dulceSabor.svg' => 'image/svg+xml',
            'logo_dulceSabor.png' => 'image/png',
        ];

        foreach ($candidatos as $archivo => $tipo) {
            $ruta = $carpeta . DIRECTORY_SEPARATOR . $archivo;

            if (is_file($ruta) && is_readable($ruta)) {
                return 'data:' . $tipo . ';base64,' . base64_encode(file_get_contents($ruta));
            }
        }

        return null;
    }

    private function generar(
        string $vista,
        array $datos,
        $papel,
        string $archivo,
        Request $request
    ) {
        if (!class_exists(Pdf::class)) {
            abort(
                500,
                'Falta instalar la librería de PDF. Ejecute: composer require barryvdh/laravel-dompdf'
            );
        }

        $producir = function (?string $logo) use ($vista, $datos, $papel) {
            return Pdf::loadView($vista, $datos + ['logo' => $logo])
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                ])
                ->setPaper($papel)
                ->output();
        };

        $logo = $this->logo();

        try {
            $contenido = $producir($logo);
        } catch (\Throwable $e) {
            // Si el logo no se pudo dibujar, se genera el PDF sin él.
            if ($logo === null) {
                throw $e;
            }

            $contenido = $producir(null);
        }

        $disposicion = $request->boolean('descargar') ? 'attachment' : 'inline';

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposicion . '; filename="' . $archivo . '"',
        ]);
    }
}
