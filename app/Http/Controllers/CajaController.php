<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;

// Dinero disponible de la empresa y su estado de cuenta (solo administrador).
class CajaController extends Controller
{
    public function index()
    {
        return view('caja.caja');
    }

    public function estado()
    {
        return response()->json([
            'success' => true,
            'caja' => Caja::resumen(),
        ]);
    }

    public function movimientos(Request $request)
    {
        $datos = $request->validate([
            'pagina' => ['nullable', 'integer', 'min:1'],
            'por_pagina' => ['nullable', 'integer', 'in:10,25,50'],
        ], [
            'pagina.integer' => 'La página solicitada no es válida.',
            'por_pagina.in' => 'La cantidad por página no es válida.',
        ]);

        return response()->json([
            'success' => true,
            'caja' => Caja::resumen(),
        ] + Caja::movimientos(
            (int) ($datos['pagina'] ?? 1),
            (int) ($datos['por_pagina'] ?? 25)
        ));
    }
}
