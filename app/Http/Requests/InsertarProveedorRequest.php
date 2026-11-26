<?php

namespace App\Http\Requests;

use App\Models\Proveedor;
use Illuminate\Foundation\Http\FormRequest;

class InsertarProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Proveedor::validaciones();
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'nombre_contacto.string' => 'El nombre de contacto debe ser una cadena de texto.',
            'nombre_contacto.max' => 'El nombre de contacto no debe superar los 100 caracteres.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no debe superar los 9 caracteres.',
            'telefono.unique' => 'Ya existe un proveedor con ese teléfono.',
            'email.string' => 'El correo debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo no debe superar los 100 caracteres.',
            'email.unique' => 'Ya existe un proveedor con ese correo electrónico.',
            'direccion.string' => 'La dirección debe ser una cadena de texto.',
            'direccion.max' => 'La dirección no debe superar los 200 caracteres.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe superar los 250 caracteres.'
        ];
    }
}
