<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarGastoRequest;
use App\Http\Requests\InsertarGastoRequest;
use App\Models\Bitacora;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GastoController extends Controller
{
    public function index()
    {
        $tipos = Gasto::TIPOS;

        return view('gasto.gastos', compact('tipos'));
    }

    private function mensajesFiltro(): array
    {
        return [
            'desde.date' => 'La fecha inicial no es válida.',
            'hasta.date' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'tipo.in' => 'El tipo de gasto seleccionado no es válido.',
        ];
    }

    // Gastos; admite los filtros opcionales desde, hasta y tipo.
    public function mostrarTodos(Request $request)
    {
        $filtros = $request->validate(
            Gasto::validacionesFiltro(),
            $this->mensajesFiltro()
        );

        return response()->json([
            'success' => true,
            'gastos' => Gasto::mostrarFiltrados(
                $filtros['desde'] ?? null,
                $filtros['hasta'] ?? null,
                $filtros['tipo'] ?? null
            ),
        ]);
    }

    // Total de gastos por tipo en el rango indicado (desde y hasta opcionales).
    public function resumen(Request $request)
    {
        $filtros = $request->validate(
            [
                'desde' => ['nullable', 'date'],
                'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            ],
            $this->mensajesFiltro()
        );

        return response()->json([
            'success' => true,
            'resumen' => Gasto::resumen(
                $filtros['desde'] ?? null,
                $filtros['hasta'] ?? null
            ),
        ]);
    }

    public function buscarGasto($id)
    {
        $gasto = Gasto::buscarGastoXId($id);

        if (!$gasto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El gasto solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'gasto' => $gasto,
        ]);
    }

    public function insertarGasto(InsertarGastoRequest $request)
    {
        $gasto = Gasto::insertar(
            $request->validated(),
            Auth::id()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'GASTOS',
            $gasto->id,
            'Se registró un gasto de ' . $gasto->tipo . ' por $' . $gasto->monto
            . ' con fecha ' . $gasto->fecha_gasto->format('d/m/Y'),
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Gasto registrado correctamente.',
            'gasto' => $gasto,
        ], 201);
    }

    public function actualizarGasto(ActualizarGastoRequest $request, $id)
    {
        $gasto = Gasto::actualizar($id, $request->validated());

        if (!$gasto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El gasto solicitado no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'GASTOS',
            $gasto->id,
            'Se actualizó el gasto #' . $gasto->id . ' (' . $gasto->tipo
            . ', $' . $gasto->monto . ')',
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Gasto actualizado correctamente.',
            'gasto' => $gasto,
        ]);
    }

    public function anularGasto($id)
    {
        $gasto = Gasto::anular($id);

        if (!$gasto) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El gasto solicitado no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'GASTOS',
            $gasto->id,
            'Se anuló el gasto #' . $gasto->id . ' (' . $gasto->tipo
            . ', $' . $gasto->monto . ')',
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Gasto anulado correctamente.',
            'gasto' => $gasto,
        ]);
    }
}
