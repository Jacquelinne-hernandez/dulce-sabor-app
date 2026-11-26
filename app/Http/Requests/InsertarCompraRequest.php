<?php

namespace App\Http\Requests;

use App\Models\Compra;
use Illuminate\Foundation\Http\FormRequest;

class InsertarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Compra::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'id_proveedor.integer' => 'El proveedor seleccionado no es válido.',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe.',

            'detalles.required' => 'Debe agregar al menos un producto a la compra.',
            'detalles.array' => 'Los detalles de la compra tienen un formato inválido.',
            'detalles.min' => 'La compra debe contener al menos un producto.',

            'detalles.*.id_producto.required' => 'Debe seleccionar un producto.',
            'detalles.*.id_producto.integer' => 'El producto seleccionado no es válido.',
            'detalles.*.id_producto.distinct' => 'No puede repetir un producto dentro de la misma compra.',
            'detalles.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',

            'detalles.*.cantidad.required' => 'Debe indicar la cantidad adquirida.',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser como mínimo una unidad.',

            'detalles.*.precio_unitario.required' => 'Debe indicar el precio unitario.',
            'detalles.*.precio_unitario.numeric' => 'El precio unitario debe ser numérico.',
            'detalles.*.precio_unitario.gt' => 'El precio unitario debe ser mayor que cero.',
            'detalles.*.precio_unitario.max' => 'El precio unitario supera el límite permitido.',
        ];
    }
}
