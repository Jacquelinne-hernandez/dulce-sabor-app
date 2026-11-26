<?php

namespace App\Support;

// Normalización de datos de entrada: así lo que se guarda siempre tiene
// el mismo formato, sin importar cómo lo escribió el usuario.
class Formato
{
    // Teléfono de El Salvador: 8 dígitos como 0000-0000. Acepta +503, 503,
    // espacios y guiones. Si no se puede normalizar, devuelve el texto
    // limpio para que la validación lo rechace con su mensaje.
    public static function telefono($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $texto);

        if (strlen($digitos) === 11 && str_starts_with($digitos, '503')) {
            $digitos = substr($digitos, 3);
        }

        if (strlen($digitos) === 8) {
            return substr($digitos, 0, 4) . '-' . substr($digitos, 4);
        }

        return $texto;
    }

    public static function correo($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = mb_strtolower(preg_replace('/\s+/', '', (string) $valor));

        return $texto === '' ? null : $texto;
    }

    // Quita espacios sobrantes. Con $propio, además pone mayúscula inicial
    // a cada palabra cuando todo vino en minúsculas o en mayúsculas.
    public static function texto($valor, bool $propio = false): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim(preg_replace('/\s+/u', ' ', (string) $valor));

        if ($texto === '') {
            return null;
        }

        if ($propio && ($texto === mb_strtolower($texto) || $texto === mb_strtoupper($texto))) {
            $texto = mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
        }

        return $texto;
    }
}
