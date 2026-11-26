<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
class ActualizarRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'nombre' => 'required|string|max:30|unique:roles,nombre,' . $id,
            'descripcion' => 'nullable|string|max:150'
        ];
    }
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.string' => 'El nombre del rol debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del rol no debe superar los 30 caracteres.',
            'nombre.unique' => 'Ya existe otro rol con ese nombre.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe superar los 150 caracteres.'
        ];
    }
}
