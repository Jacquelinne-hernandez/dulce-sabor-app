<?php

namespace App\Http\Requests;

use App\Models\Estado;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Estado::validaciones($this->route('id'));
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El nombre del estado es obligatorio.',
            'estado.string' => 'El estado debe ser una cadena de texto.',
            'estado.max' => 'El estado no debe superar los 50 caracteres.',
            'estado.unique' => 'Ya existe otro registro con ese estado.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe superar los 250 caracteres.'
        ];
    }
}
