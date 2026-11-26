<?php

namespace App\Http\Requests;

//  ActualizarMesaRequest

use App\Models\Mesa;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarMesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Mesa::validaciones($this->route('id'));
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
            'capacidad.min' => 'La capacidad debe ser como mínimo de una persona.'
        ];
    }
}
