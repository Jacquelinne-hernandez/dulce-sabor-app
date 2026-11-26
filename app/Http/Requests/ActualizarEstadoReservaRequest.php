<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // "atendida" no se puede elegir: la fija el sistema al registrar
    // el pedido de la reserva.
    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                'in:confirmada,cancelada',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.string' => 'El estado debe ser texto.',
            'estado.in' => 'El estado seleccionado no es válido. Una reserva solo puede confirmarse o cancelarse; pasa a atendida al registrar su pedido.',
        ];
    }
}
