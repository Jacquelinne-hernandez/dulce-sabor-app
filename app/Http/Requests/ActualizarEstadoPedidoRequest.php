<?php

namespace App\Http\Requests;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarEstadoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Un pedido solo se puede cancelar: pasa a "completado" cuando se
    // paga su factura.
    public function rules(): array
    {
        return Pedido::reglasEstado();
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe indicar el estado.',
            'estado.in' => 'Un pedido solo puede cancelarse; se completa al pagarse su factura.',

            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.max' => 'El motivo no debe superar los 200 caracteres.',
        ];
    }
}
