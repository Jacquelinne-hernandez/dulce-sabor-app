<?php

namespace App\Http\Requests;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;

class InsertarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Pedido::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_cliente.required' => 'Debe seleccionar un cliente.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',

            'id_mesa.exists' => 'La mesa seleccionada no existe.',

            'detalles.required' => 'Debe agregar al menos un producto al pedido.',
            'detalles.min' => 'El pedido debe contener al menos un producto.',

            'detalles.*.id_producto.required' => 'Debe seleccionar un producto.',
            'detalles.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
            'detalles.*.id_producto.distinct' => 'No puede agregar el mismo producto más de una vez.',

            'detalles.*.cantidad.required' => 'Debe indicar la cantidad del producto.',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser como mínimo 1.',

            'observaciones.string' => 'Las observaciones deben ser texto.',
        ];
    }
}
