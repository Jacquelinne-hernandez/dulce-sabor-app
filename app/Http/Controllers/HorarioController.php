<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarHorarioRequest;
use App\Models\Bitacora;
use App\Models\Horario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HorarioController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Vista principal
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('horario.horarios');
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar todos los horarios
    |--------------------------------------------------------------------------
    */

    public function mostrarTodos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'horarios' => Horario::mostrarTodos()
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar horario por ID
    |--------------------------------------------------------------------------
    */

    public function buscarHorario($id): JsonResponse
    {
        $horario = Horario::buscarHorarioXId($id);

        if (!$horario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El horario solicitado no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'horario' => $horario
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar horario por día
    |--------------------------------------------------------------------------
    */

    public function buscarHorarioXDia($dia): JsonResponse
    {
        $horario = Horario::buscarHorarioXDia($dia);

        if (!$horario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No existe un horario configurado para ese día.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'horario' => $horario
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar horario
    |--------------------------------------------------------------------------
    */

    public function actualizarHorario(
        ActualizarHorarioRequest $request,
                                 $id
    ): JsonResponse {
        $horario = Horario::actualizar($id, $request->validated());

        if (!$horario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El horario solicitado no existe.'
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'HORARIOS',
            $horario->id,
            'Se actualizó el horario del día: ' . $horario->nombre_dia,
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Horario actualizado correctamente.',
            'horario' => $horario
        ], 200);
    }
}
