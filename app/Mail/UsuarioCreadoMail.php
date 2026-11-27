<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/*
 * Aviso al nuevo usuario de que se creó su cuenta.
 *
 * Por seguridad NO incluye la contraseña: el administrador se la entrega
 * por otro medio y el sistema obliga a cambiarla en el primer ingreso.
 */
class UsuarioCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Usuario $usuario)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Su cuenta en ' . config('app.name') . ' fue creada'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.usuario_creado');
    }
}
