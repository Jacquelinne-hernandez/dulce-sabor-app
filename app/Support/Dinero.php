<?php

namespace App\Support;

// Todo el dinero se calcula en centavos (enteros) para evitar errores de
// precisión decimal, y se guarda en columnas DECIMAL(10,2).
class Dinero
{
    // DECIMAL(10,2): máximo 99,999,999.99
    public const LIMITE_CENTAVOS = 9999999999;

    public static function centavos($valor): int
    {
        return (int) round(((float) $valor) * 100);
    }

    public static function formato(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
