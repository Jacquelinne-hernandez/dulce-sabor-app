<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual' => [
                'required',
                'string',
                'current_password',
            ],

            'password' => array_merge(
                Usuario::reglasPassword(),
                ['different:password_actual']
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required' => 'Escriba su contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ] + Usuario::mensajesPassword();
    }

    public function attributes(): array
    {
        return [
            'password_actual' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ];
    }
}
