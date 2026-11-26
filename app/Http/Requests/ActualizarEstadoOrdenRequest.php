<?php

namespace App\Http\Requests;

use App\Models\Orden;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Orden::reglasEstado();
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido. Para cancelar, cancele el pedido.',
        ];
    }
}
