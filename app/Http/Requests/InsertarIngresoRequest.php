<?php

namespace App\Http\Requests;

use App\Models\Ingreso;
use Illuminate\Foundation\Http\FormRequest;

class InsertarIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Ingreso::validaciones();
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Debe seleccionar el tipo de ingreso.',
            'tipo.in' => 'El tipo de ingreso seleccionado no es válido.',

            'descripcion.required_if' => 'Describa el ingreso cuando el tipo es "otros".',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',

            'monto.required' => 'Debe indicar el monto del ingreso.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'El monto supera el límite permitido.',

            'fecha_ingreso.required' => 'Debe indicar la fecha del ingreso.',
            'fecha_ingreso.date' => 'La fecha del ingreso no es válida.',
            'fecha_ingreso.before_or_equal' => 'La fecha del ingreso no puede ser futura.',
        ];
    }
}
