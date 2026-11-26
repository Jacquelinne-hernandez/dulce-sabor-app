<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Support\Dashboard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
            + ($factura->pedido?->reserva?->mesas?->count() ?? 0);

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

        return $factura->load([
            'pedido.mesa',
            'pedido.reserva.mesas',
            'pedido.orden',
            'detalles',
            'pagos',
        ]);
    }

    // Logo incrustado. Solo se admite PNG o JPG: dompdf se detiene o
    // consume mucha memoria con los SVG complejos, y eso causaba errores.
    // Sin imagen, los documentos llevan el nombre del negocio en texto.
    private function logo(): ?string
    {
        $carpeta = public_path('images');

        $candidatos = [
            'logo_dulceSabor.png' => 'image/png',
            'logo_dulceSabor.jpg' => 'image/jpeg',
            'logo.png' => 'image/png',
        ];

        foreach ($candidatos as $archivo => $tipo) {
            $ruta = $carpeta . DIRECTORY_SEPARATOR . $archivo;

            if (is_file($ruta) && is_readable($ruta) && filesize($ruta) < 2 * 1024 * 1024) {
                return 'data:' . $tipo . ';base64,' . base64_encode(file_get_contents($ruta));
            }
        }

        return null;
    }

    // dompdf escribe fuentes y temporales; si las carpetas no existen o no
    // tienen permiso de escritura, falla con un error 500.
    private function prepararCarpetas(): array
    {
        $fuentes = storage_path('fonts');
        $temporal = storage_path('app/dompdf');

        File::ensureDirectoryExists($fuentes);
        File::ensureDirectoryExists($temporal);

        return [
            'fontDir' => $fuentes,
            'fontCache' => $fuentes,
            'tempDir' => $temporal,
        ];
    }

    private function generar(
        string $vista,
        array $datos,
        $papel,
        string $archivo,
        Request $request
    ) {
        if (!class_exists(Pdf::class)) {
            return $this->error(
                'Falta instalar la librería de PDF. Ejecute en la carpeta del proyecto: composer require barryvdh/laravel-dompdf'
            );
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        try {
            $opciones = $this->prepararCarpetas() + [
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
            ];

            $contenido = Pdf::loadView($vista, $datos + ['logo' => $this->logo()])
                ->setOptions($opciones)
                ->setPaper($papel)
                ->output();
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el PDF (' . $vista . '): ' . $e->getMessage(), [
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return $this->error(
                'No se pudo generar el documento: ' . $e->getMessage()
            );
        }

        $disposicion = $request->boolean('descargar') ? 'attachment' : 'inline';

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposicion . '; filename="' . $archivo . '"',
        ]);
    }

    // Página de error legible (el PDF se abre en otra pestaña).
    private function error(string $mensaje)
    {
        $html = '<!doctype html><html lang="es"><meta charset="utf-8"><title>No se pudo generar el documento</title>'
            . '<body style="font-family:sans-serif;max-width:560px;margin:12vh auto;padding:0 1rem;color:#4a2a63">'
            . '<h1 style="font-size:1.4rem">No se pudo generar el documento</h1>'
            . '<p>' . e($mensaje) . '</p>'
            . '<p style="color:#777;font-size:.9rem">El detalle técnico quedó en storage/logs/laravel.log.</p>'
            . '<p><a href="javascript:window.close()">Cerrar esta pestaña</a></p></body></html>';

        return response($html, 500);
    }
}
