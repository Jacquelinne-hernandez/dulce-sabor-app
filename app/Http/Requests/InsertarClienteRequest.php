<?php

namespace App\Http\Requests;

// InsertarClienteRequest:

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;

class InsertarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Cliente::validaciones();
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del cliente es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe superar los 50 caracteres.',

            'apellido.required' => 'El apellido del cliente es obligatorio.',
            'apellido.string' => 'El apellido debe ser una cadena de texto.',
            'apellido.max' => 'El apellido no debe superar los 50 caracteres.',

            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe superar los 20 caracteres.',

            'correo.string' => 'El correo debe ser una cadena de texto.',
            'correo.email' => 'El correo electrónico no tiene un formato válido.',
            'correo.max' => 'El correo no debe superar los 100 caracteres.',
            'correo.unique' => 'El correo electrónico ya está registrado.'
        ];
    }
}
