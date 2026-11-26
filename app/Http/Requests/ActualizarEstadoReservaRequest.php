<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                'in:pendiente,confirmada,cancelada,atendida',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.string' => 'El estado debe ser texto.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
