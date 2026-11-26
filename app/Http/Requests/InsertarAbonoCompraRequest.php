<?php

namespace App\Http\Requests;

use App\Models\PagoCompra;
use Illuminate\Foundation\Http\FormRequest;

class InsertarAbonoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return PagoCompra::validaciones();
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'Debe indicar el monto del abono.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'El monto supera el límite permitido.',

            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no deben superar los 255 caracteres.',
        ];
    }
}
