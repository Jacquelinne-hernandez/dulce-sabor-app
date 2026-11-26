<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Factura;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;

class HomeController extends Controller
{
    public function index()
    {
        return view('home.index');
    }

    public function dashboard()
    {
        $hoy = now()->toDateString();

        // Indicadores generales del día.
        $resumen = [
            'pedidos_hoy' => Pedido::whereDate('fecha', $hoy)->count(),

            'pedidos_en_curso' => Pedido::whereIn(
                'estado',
                ['pendiente', 'en_preparacion', 'listo']
            )->count(),

            'ventas_hoy' => Factura::where('estado', 'emitida')
                ->whereDate('fecha_emision', $hoy)
                ->sum('total'),

            'reservas_hoy' => Reserva::whereDate('fecha', $hoy)
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->count(),

            'mesas_disponibles' => Mesa::where('estado', 'disponible')->count(),

            'clientes_activos' => Cliente::where('id_estado', Estado::ACTIVO)->count(),
        ];

        $productosStockBajo = Producto::stockBajo();

        $ultimosPedidos = Pedido::with(['cliente', 'mesa'])
            ->orderByDesc('fecha')
            ->limit(5)
            ->get();

        $usuariosPorEstado = Usuario::estadosDeUsuario();

        return view('home.dashboard', compact(
            'resumen',
            'productosStockBajo',
            'ultimosPedidos',
            'usuariosPorEstado'
        ));
    }
}
