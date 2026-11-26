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
            'tipo.required' => 'Debe indicar el tipo de pedido.',
            'tipo.in' => 'El tipo de pedido no es válido (local, llevar o reserva).',

            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',

            'id_mesa.required_if' => 'Debe seleccionar la mesa para el consumo en el local.',
            'id_mesa.exists' => 'La mesa seleccionada no existe.',
            'id_mesa.prohibited_unless' => 'La mesa solo se indica en pedidos para consumir en el local.',

            'id_reserva.required_if' => 'Debe seleccionar la reserva a atender.',
            'id_reserva.exists' => 'La reserva seleccionada no existe.',
            'id_reserva.prohibited_unless' => 'La reserva solo se indica en pedidos de reserva.',

            'fecha_programada.date' => 'La fecha programada no es válida.',
            'fecha_programada.prohibited_unless' => 'Solo los pedidos para llevar pueden tener fecha programada.',

            'entrega_inmediata.boolean' => 'El valor de entrega inmediata no es válido.',

            'detalles.required' => 'Debe agregar al menos un producto al pedido.',
            'detalles.array' => 'Los productos del pedido no son válidos.',
            'detalles.min' => 'El pedido debe contener al menos un producto.',

            'detalles.*.id_producto.required' => 'Debe seleccionar un producto.',
            'detalles.*.id_producto.integer' => 'Uno de los productos seleccionados no es válido.',
            'detalles.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
            'detalles.*.id_producto.distinct' => 'No puede agregar el mismo producto más de una vez.',

            'detalles.*.cantidad.required' => 'Debe indicar la cantidad del producto.',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser como mínimo 1.',
            'detalles.*.cantidad.max' => 'La cantidad por producto no puede superar 10,000 unidades.',

            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no deben superar los 500 caracteres.',
        ];
    }
}
