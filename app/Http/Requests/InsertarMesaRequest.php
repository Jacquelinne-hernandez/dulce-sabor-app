<?php

namespace App\Http\Requests;

// InsertarMesaRequest

use App\Models\Mesa;
use Illuminate\Foundation\Http\FormRequest;

class InsertarMesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Mesa::validaciones();
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'El número de mesa es obligatorio.',
            'numero.integer' => 'El número de mesa debe ser numérico.',
            'numero.min' => 'El número de mesa debe ser mayor que cero.',
            'numero.unique' => 'El número de mesa ya está registrado.',

            'capacidad.required' => 'La capacidad de la mesa es obligatoria.',
            'capacidad.integer' => 'La capacidad debe ser un número entero.',
            'capacidad.min' => 'La capacidad debe ser como mínimo de una persona.',

            'tarifa_hora.required' => 'La tarifa por hora es obligatoria.',
            'tarifa_hora.numeric' => 'La tarifa por hora debe ser un valor numérico.',
            'tarifa_hora.min' => 'La tarifa por hora no puede ser negativa.',
            'tarifa_hora.max' => 'La tarifa por hora supera el límite permitido.'
        ];
    }
}
