<?php

namespace App\Http\Controllers;

use App\Support\Dashboard;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home.index');
    }

    // Cada rol ve su dashboard. El del administrador admite ?desde=&hasta=
    // (por defecto, el mes en curso).
    public function dashboard(Request $request)
    {
        $esAdmin = auth()->user()->rol?->nombre === 'Administrador';

        try {
            if ($esAdmin) {
                $datos = Dashboard::admin(
                    $request->query('desde'),
                    $request->query('hasta')
                );

                return view('home.dashboard_admin', compact('datos'));
            }

            $datos = Dashboard::operador();

            return view('home.dashboard_operador', compact('datos'));
        } catch (QueryException $e) {
            // 42S02: tabla o vista inexistente.
            if ($e->getCode() === '42S02') {
                abort(
                    503,
                    'Faltan las vistas SQL del dashboard. Ejecute el archivo '
                    . 'database/vistas/dashboard_views.sql en la base de datos.'
                );
            }

            throw $e;
        }
    }
}
