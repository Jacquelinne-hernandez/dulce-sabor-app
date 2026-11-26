<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarIngresoRequest;
use App\Http\Requests\InsertarIngresoRequest;
use App\Models\Bitacora;
use App\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Ingresos externos al giro del negocio (solo administrador).
class IngresoController extends Controller
{
    public function index()
    {
        $tipos = Ingreso::TIPOS;

        return view('ingreso.ingresos', compact('tipos'));
    }

    private function mensajesFiltro(): array
    {
        return [
            'desde.date' => 'La fecha inicial no es válida.',
            'hasta.date' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'tipo.in' => 'El tipo de ingreso seleccionado no es válido.',
        ];
    }

    public function mostrarTodos(Request $request)
    {
        $filtros = $request->validate(
            Ingreso::validacionesFiltro(),
            $this->mensajesFiltro()
        );

        return response()->json([
            'success' => true,
            'ingresos' => Ingreso::mostrarFiltrados(
                $filtros['desde'] ?? null,
                $filtros['hasta'] ?? null,
                $filtros['tipo'] ?? null
            ),
        ]);
    }

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
            'resumen' => Ingreso::resumen(
                $filtros['desde'] ?? null,
                $filtros['hasta'] ?? null
            ),
        ]);
    }

    public function buscarIngreso($id)
    {
        $ingreso = Ingreso::buscarIngresoXId($id);

        if (!$ingreso) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El ingreso solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'ingreso' => $ingreso,
        ]);
    }

    public function insertarIngreso(InsertarIngresoRequest $request)
    {
        $ingreso = Ingreso::insertar(
            $request->validated(),
            Auth::id()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'INGRESOS',
            $ingreso->id,
            'Se registró un ingreso externo (' . $ingreso->tipo . ') por $' . $ingreso->monto
            . ' con fecha ' . $ingreso->fecha_ingreso->format('d/m/Y'),
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Ingreso registrado correctamente.',
            'ingreso' => $ingreso,
        ], 201);
    }

    public function actualizarIngreso(ActualizarIngresoRequest $request, $id)
    {
        $ingreso = Ingreso::actualizar($id, $request->validated());

        if (!$ingreso) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El ingreso solicitado no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'INGRESOS',
            $ingreso->id,
            'Se actualizó el ingreso #' . $ingreso->id . ' (' . $ingreso->tipo
            . ', $' . $ingreso->monto . ')',
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Ingreso actualizado correctamente.',
            'ingreso' => $ingreso,
        ]);
    }

    public function anularIngreso($id)
    {
        $ingreso = Ingreso::anular($id);

        if (!$ingreso) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El ingreso solicitado no existe.',
            ], 404);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'INGRESOS',
            $ingreso->id,
            'Se anuló el ingreso #' . $ingreso->id . ' (' . $ingreso->tipo
            . ', $' . $ingreso->monto . ')',
            Auth::user()?->nombre
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Ingreso anulado correctamente.',
            'ingreso' => $ingreso,
        ]);
    }
}
