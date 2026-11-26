<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado
     * para realizar la solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el inicio de sesión.
     */
    public function rules(): array
    {
        return Usuario::loginRules();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es requerido para iniciar sesión.',
            'email.string' => 'El correo electrónico debe ser texto.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.max' => 'El correo no debe exceder los 100 caracteres.',

            'password.required' => 'La contraseña es requerida para acceder.',
            'password.string' => 'La contraseña debe ser texto.',
        ];
    }

    /**
     * Nombres personalizados de los atributos.
     */
    public function attributes(): array
    {
        return [
            'email' => 'Correo electrónico de usuario',
            'password' => 'Contraseña',
        ];
    }
}
