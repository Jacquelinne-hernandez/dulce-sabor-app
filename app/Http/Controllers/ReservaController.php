<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEstadoReservaRequest;
use App\Http\Requests\InsertarReservaRequest;
use App\Models\Bitacora;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservaController extends Controller
{
    // Las mesas se eligen según el horario (mesas-disponibles) y el
    // cliente se busca por código o nombre, así que la vista no necesita
    // listas precargadas.
    public function index()
    {
        return view('reserva.reservas');
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

    // Mesas que se pueden reservar en la fecha y el horario indicados.
    public function mesasDisponibles(Request $request)
    {
        $datos = $request->validate(
            Reserva::validacionesHorario(),
            [
                'fecha.required' => 'Debe indicar la fecha de la reserva.',
                'fecha.date' => 'La fecha proporcionada no es válida.',
                'fecha.after_or_equal' => 'No puede consultar fechas anteriores a hoy.',

                'hora_inicio.required' => 'Debe indicar la hora de inicio.',
                'hora_inicio.date_format' => 'La hora de inicio debe tener un formato válido (HH:mm).',

                'hora_fin.required' => 'Debe indicar la hora de finalización.',
                'hora_fin.date_format' => 'La hora de finalización debe tener un formato válido (HH:mm).',
                'hora_fin.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
            ]
        );

        return response()->json([
            'success' => true,
            'mesas' => Reserva::mesasDisponibles(
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin']
            ),
        ]);
    }

    // Cotización del cobro por las mesas (no guarda nada).
    public function cotizar(Request $request)
    {
        $datos = $request->validate(
            array_merge(
                Reserva::validacionesMesas(),
                Reserva::validacionesRango()
            ),
            [
                'id_mesas.required' => 'Debe seleccionar al menos una mesa.',
                'id_mesas.array' => 'Las mesas seleccionadas no son válidas.',
                'id_mesas.min' => 'Debe seleccionar al menos una mesa.',
                'id_mesas.*.integer' => 'Una de las mesas seleccionadas no es válida.',
                'id_mesas.*.distinct' => 'No puede seleccionar la misma mesa dos veces.',
                'id_mesas.*.exists' => 'Una de las mesas seleccionadas no existe.',

                'hora_inicio.required' => 'Debe indicar la hora de inicio.',
                'hora_inicio.date_format' => 'La hora de inicio debe tener un formato válido (HH:mm).',

                'hora_fin.required' => 'Debe indicar la hora de finalización.',
                'hora_fin.date_format' => 'La hora de finalización debe tener un formato válido (HH:mm).',
                'hora_fin.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
            ]
        );

        return response()->json([
            'success' => true,
            'cotizacion' => Reserva::cotizar(
                $datos['id_mesas'],
                $datos['hora_inicio'],
                $datos['hora_fin']
            ),
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
            'Se registró la reserva #' . $reserva->id
            . ' del cliente ' . $reserva->cliente->codigo
            . ' (' . $reserva->cliente->nombre_completo . ')'
            . ' para las mesas: ' . $reserva->mesas->pluck('numero')->implode(', '),
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
            $request->validated()['estado'],
            Auth::id()
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
            'Se cambió el estado de la reserva #' . $reserva->id . ' de '
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
