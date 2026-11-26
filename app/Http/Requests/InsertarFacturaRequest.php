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

            'puntos_canjeados.integer' => 'Los puntos a canjear deben ser un número entero.',
            'puntos_canjeados.min' => 'Los puntos a canjear no pueden ser negativos.',
            'puntos_canjeados.max' => 'La cantidad de puntos a canjear supera el límite permitido.',
        ];
    }
}
