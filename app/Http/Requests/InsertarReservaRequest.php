<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;

class InsertarReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Reserva::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_cliente.required' => 'Debe seleccionar un cliente.',
            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',

            'id_mesa.required' => 'Debe seleccionar una mesa.',
            'id_mesa.integer' => 'La mesa seleccionada no es válida.',
            'id_mesa.exists' => 'La mesa seleccionada no existe.',

            'fecha.required' => 'La fecha de la reserva es obligatoria.',
            'fecha.date' => 'La fecha proporcionada no es válida.',
            'fecha.after_or_equal' => 'No puede registrar reservas para fechas anteriores.',

            'hora_inicio.required' => 'Debe indicar la hora de inicio.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener un formato válido (HH:mm).',

            'hora_fin.required' => 'Debe indicar la hora de finalización.',
            'hora_fin.date_format' => 'La hora de finalización debe tener un formato válido (HH:mm).',
            'hora_fin.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',

            'cantidad_personas.required' => 'Debe indicar la cantidad de personas.',
            'cantidad_personas.integer' => 'La cantidad de personas debe ser un número entero.',
            'cantidad_personas.min' => 'La reserva debe incluir al menos una persona.',

            'observaciones.string' => 'Las observaciones deben ser texto.',
        ];
    }
}
