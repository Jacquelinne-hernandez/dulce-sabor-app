<?php

namespace App\Http\Requests;

// InsertarClienteRequest:

use App\Models\Cliente;
use App\Http\Requests\Concerns\NormalizaDatos;
use Illuminate\Foundation\Http\FormRequest;

class InsertarClienteRequest extends FormRequest
{
    use NormalizaDatos;

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
            'nombre.regex' => 'El nombre solo puede contener letras, espacios, punto, apóstrofo y guion.',

            'apellido.required' => 'El apellido del cliente es obligatorio.',
            'apellido.string' => 'El apellido debe ser una cadena de texto.',
            'apellido.max' => 'El apellido no debe superar los 50 caracteres.',
            'apellido.regex' => 'El apellido solo puede contener letras, espacios, punto, apóstrofo y guion.',

            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos con el formato 0000-0000.',

            'correo.string' => 'El correo debe ser una cadena de texto.',
            'correo.email' => 'El correo electrónico no tiene un formato válido.',
            'correo.max' => 'El correo no debe superar los 100 caracteres.',
            'correo.unique' => 'El correo electrónico ya está registrado.'
        ];
    }
}
