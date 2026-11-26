<?php

namespace App\Http\Requests;

use App\Models\Horario;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Horario::validaciones();
    }

    public function messages(): array
    {
        return [
            'activo.required' => 'Debe indicar si el establecimiento atiende ese día.',
            'activo.boolean' => 'El valor de atención del día no es válido.',
            'hora_apertura.required_if' => 'La hora de apertura es obligatoria para los días de atención.',
            'hora_apertura.date_format' => 'La hora de apertura debe tener un formato válido (HH:mm).',
            'hora_cierre.required_if' => 'La hora de cierre es obligatoria para los días de atención.',
            'hora_cierre.date_format' => 'La hora de cierre debe tener un formato válido (HH:mm).',
            'hora_cierre.after' => 'La hora de cierre debe ser posterior a la hora de apertura.'
        ];
    }
}
