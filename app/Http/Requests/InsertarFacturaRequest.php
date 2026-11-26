<?php

namespace App\Http\Requests;

use App\Models\Factura;
use Illuminate\Foundation\Http\FormRequest;

class InsertarFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Factura::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_pedido.required' => 'Debe seleccionar un pedido.',
            'id_pedido.integer' => 'El identificador del pedido no es válido.',
            'id_pedido.exists' => 'El pedido seleccionado no existe.',

            'efectivo_recibido.required' => 'Debe indicar el efectivo recibido.',
            'efectivo_recibido.numeric' => 'El efectivo recibido debe ser un valor numérico.',
            'efectivo_recibido.gt' => 'El efectivo recibido debe ser mayor que cero.',
            'efectivo_recibido.max' => 'El efectivo recibido supera el límite permitido.',
        ];
    }
}
