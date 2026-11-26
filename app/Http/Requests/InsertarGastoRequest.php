<?php

namespace App\Http\Requests;

use App\Models\Gasto;
use Illuminate\Foundation\Http\FormRequest;

class InsertarGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Gasto::validaciones();
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Debe seleccionar el tipo de gasto.',
            'tipo.in' => 'El tipo de gasto seleccionado no es válido.',

            'descripcion.required_if' => 'Describa el gasto cuando el tipo es "otros".',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',

            'monto.required' => 'Debe indicar el monto del gasto.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'El monto supera el límite permitido.',

            'fecha_gasto.required' => 'Debe indicar la fecha del gasto.',
            'fecha_gasto.date' => 'La fecha del gasto no es válida.',
            'fecha_gasto.before_or_equal' => 'La fecha del gasto no puede ser futura.',
        ];
    }
}
