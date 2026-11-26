<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Producto::validaciones($this->route('id'));
    }

    public function messages(): array
    {
        return [
            'id_categoria.required' => 'Debe seleccionar una categoría.',
            'id_categoria.integer' => 'La categoría seleccionada no es válida.',
            'id_categoria.exists' => 'La categoría seleccionada no existe.',

            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un producto con ese nombre.',

            'precio.required' => 'El precio del producto es obligatorio.',
            'precio.numeric' => 'El precio debe ser un valor numérico.',
            'precio.gt' => 'El precio debe ser mayor que cero.',
            'precio.max' => 'El precio supera el límite permitido.',

            'costo.required' => 'El costo del producto es obligatorio.',
            'costo.numeric' => 'El costo debe ser un valor numérico.',
            'costo.min' => 'El costo no puede ser negativo.',
            'costo.max' => 'El costo supera el límite permitido.',

            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',

            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',

            'descripcion.string' => 'La descripción debe ser texto.',

            'imagen.string' => 'La ruta de la imagen debe ser texto.',
            'imagen.max' => 'La ruta de la imagen no puede superar los 255 caracteres.',
        ];
    }
}
