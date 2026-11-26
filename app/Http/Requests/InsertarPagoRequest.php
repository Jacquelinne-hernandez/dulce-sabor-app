<?php

namespace App\Http\Requests;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;

class InsertarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Pago::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_factura.required' => 'Debe seleccionar una factura.',
            'id_factura.integer' => 'El identificador de la factura no es válido.',
            'id_factura.exists' => 'La factura seleccionada no existe.',

            'metodo.required' => 'Debe seleccionar el método de pago.',
            'metodo.in' => 'El método de pago debe ser efectivo o tarjeta.',

            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'El monto supera el límite permitido.',

            'efectivo_recibido.required_if' => 'Debe indicar el efectivo recibido.',
            'efectivo_recibido.numeric' => 'El efectivo recibido debe ser un valor numérico.',
            'efectivo_recibido.gt' => 'El efectivo recibido debe ser mayor que cero.',
            'efectivo_recibido.max' => 'El efectivo recibido supera el límite permitido.',

            'tarjeta_titular.required_if' => 'Debe indicar el nombre del titular de la tarjeta.',
            'tarjeta_titular.min' => 'El nombre del titular debe tener al menos 3 caracteres.',
            'tarjeta_titular.max' => 'El nombre del titular no debe superar los 60 caracteres.',

            'tarjeta_numero.required_if' => 'Debe indicar el número de la tarjeta.',
            'tarjeta_numero.max' => 'El número de tarjeta es demasiado largo.',

            'tarjeta_vencimiento.required_if' => 'Debe indicar el vencimiento de la tarjeta.',
            'tarjeta_vencimiento.regex' => 'El vencimiento debe tener el formato MM/AA.',

            'tarjeta_cvv.required_if' => 'Debe indicar el CVV de la tarjeta.',
            'tarjeta_cvv.digits_between' => 'El CVV debe tener 3 o 4 dígitos.',
        ];
    }
}
