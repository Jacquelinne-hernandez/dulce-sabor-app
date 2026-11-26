<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class InsertarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Usuario::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_rol.required' => 'El rol del usuario es obligatorio.',
            'id_rol.integer' => 'El rol seleccionado no es válido.',
            'id_rol.exists' => 'El rol seleccionado no existe.',

            'id_estado.required' => 'El estado del usuario es obligatorio.',
            'id_estado.integer' => 'El estado seleccionado no es válido.',
            'id_estado.exists' => 'El estado seleccionado no existe.',

            'nombre.required' => 'El nombre del usuario es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe superar los 50 caracteres.',

            'apellido.required' => 'El apellido del usuario es obligatorio.',
            'apellido.string' => 'El apellido debe ser una cadena de texto.',
            'apellido.max' => 'El apellido no debe superar los 50 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo no debe superar los 100 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no debe superar los 255 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',

            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe superar los 9 caracteres.',
            'telefono.unique' => 'Ya existe un usuario con ese teléfono.'
        ];
    }
}
