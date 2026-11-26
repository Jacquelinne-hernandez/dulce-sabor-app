<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => [
                'required',
                'string',
                'min:5',
                'max:200',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe indicar el motivo de la anulación.',
            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo no debe superar los 200 caracteres.',
        ];
    }
}
