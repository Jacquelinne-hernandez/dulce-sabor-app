<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
 * Envío de correos del sistema.
 *
 * - El correo se envía DESPUÉS de responder al usuario, por lo que no hace
 *   más lenta la operación que lo origina.
 * - Un fallo de envío nunca interrumpe la operación: se registra en
 *   storage/logs/laravel.log y se continúa.
 * - Con MAIL_MAILER=log (valor por defecto) el correo no sale a Internet:
 *   queda escrito en el log, ideal para probar sin configurar un servidor.
 *   Para enviar de verdad, configure MAIL_* en el archivo .env.
 */
class Correo
{
    public static function enviar(?string $destino, Mailable $mensaje): bool
    {
        $destino = Formato::correo($destino);

        if (!$destino || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        app()->terminating(function () use ($destino, $mensaje) {
            try {
                Mail::to($destino)->send($mensaje);
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el correo a ' . $destino . ': ' . $e->getMessage());
            }
        });

        return true;
    }
}
