<?php

namespace App\Http\Requests;

use App\Models\Mesa;
use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoMesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Mesa::reglasEstado();
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.string' => 'El estado debe ser texto.',
            'estado.in' => 'El estado seleccionado no es válido.'
        ];
    }
}
