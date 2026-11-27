<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubirImagenProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . Producto::IMAGEN_MAX_KB,
                Rule::dimensions()->maxWidth(6000)->maxHeight(6000),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'imagen.required' => 'Seleccione una imagen.',
            'imagen.file' => 'El archivo seleccionado no es válido.',
            'imagen.uploaded' => 'No se pudo subir la imagen (supera el tamaño que permite el servidor).',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 2 MB.',
            'imagen.dimensions' => 'La imagen es demasiado grande (máximo 6000 x 6000 píxeles).',
        ];
    }
}
