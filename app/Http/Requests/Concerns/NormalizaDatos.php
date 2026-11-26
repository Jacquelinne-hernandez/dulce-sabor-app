<?php

namespace App\Http\Requests\Concerns;

use App\Support\Formato;

// Normaliza teléfono, correo y nombres antes de validar.
trait NormalizaDatos
{
    protected function prepareForValidation(): void
    {
        $datos = [];

        if ($this->has('telefono')) {
            $datos['telefono'] = Formato::telefono($this->input('telefono'));
        }

        foreach (['correo', 'email'] as $campo) {
            if ($this->has($campo) && is_string($this->input($campo))) {
                $datos[$campo] = Formato::correo($this->input($campo));
            }
        }

        // Nombres de personas (clientes, usuarios, contactos).
        $esPersona = str_contains(class_basename($this), 'Cliente')
            || str_contains(class_basename($this), 'Usuario');

        foreach (['nombre', 'apellido', 'nombre_contacto'] as $campo) {
            if ($this->has($campo) && is_string($this->input($campo))) {
                $propio = $campo === 'nombre' ? $esPersona : true;
                $datos[$campo] = Formato::texto($this->input($campo), $propio);
            }
        }

        if ($datos) {
            $this->merge($datos);
        }
    }
}
