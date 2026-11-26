<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEstadoReservaRequest;
use App\Http\Requests\InsertarReservaRequest;
use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservaController extends Controller
{
    public function index()
    {
        $clientes = Cliente::where('id_estado', '!=', 3)
            ->orderBy('nombre')
            ->get();

        $mesas = Mesa::where('estado', '!=', 'mantenimiento')
            ->orderBy('numero')
            ->get();

        return view('reserva.reservas', compact(
            'clientes',
            'mesas'
        ));
    }

    public function mostrarTodos()
    {
        return response()->json([
            'success' => true,
            'reservas' => Reserva::mostrarTodos(),
        ]);
    }

    public function buscarReserva($id)
    {
        $reserva = Reserva::buscarReservaXId($id);

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La reserva solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'reserva' => $reserva,
        ]);
    }

    public function mostrarPorFecha(Request $request)
    {
        $datos = $request->validate(
            [
                'fecha' => [
                    'required',
                    'date',
                ],
            ],
            [
                'fecha.required' => 'Debe indicar la fecha que desea consultar.',
                'fecha.date' => 'La fecha proporcionada no es válida.',
            ]
        );

        return response()->json([
            'success' => true,
            'reservas' => Reserva::mostrarPorFecha($datos['fecha']),
        ]);
    }

    public function mostrarPorEstado(Request $request)
    {
        $datos = $request->validate(
            [
                'estado' => [
                    'required',
                    'string',
                    'in:pendiente,confirmada,cancelada,atendida',
                ],
            ],
            [
                'estado.required' => 'Debe indicar el estado que desea consultar.',
                'estado.in' => 'El estado seleccionado no es válido.',
            ]
        );

        return response()->json([
            'success' => true,
            'reservas' => Reserva::mostrarPorEstado($datos['estado']),
        ]);
    }

    public function insertarReserva(InsertarReservaRequest $request)
    {
        $reserva = Reserva::insertar(
            $request->validated(),
            Auth::id()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'RESERVAS',
            $reserva->id,
            'Se registró una reserva para el cliente: '
            . $reserva->cliente->nombre,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Reserva registrada correctamente.',
            'reserva' => $reserva,
        ], 201);
    }

    public function actualizarEstado(
        ActualizarEstadoReservaRequest $request,
                                       $id
    ) {
        $reservaAnterior = Reserva::buscarReservaXId($id);

        if (!$reservaAnterior) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La reserva solicitada no existe.',
            ], 404);
        }

        $estadoAnterior = $reservaAnterior->estado;

        $reserva = Reserva::cambiarEstado(
            $id,
            $request->validated()['estado']
        );

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La reserva solicitada no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'RESERVAS',
            $reserva->id,
            'Se cambió el estado de la reserva de '
            . $estadoAnterior . ' a ' . $reserva->estado,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Estado de la reserva actualizado correctamente.',
            'reserva' => $reserva,
        ]);
    }
}
