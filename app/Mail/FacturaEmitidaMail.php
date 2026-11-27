<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/*
 * Aviso al cliente de que se emitió su factura.
 *
 * Base para ampliar más adelante (por ejemplo, adjuntar el PDF que genera
 * DocumentoController con attachments()).
 */
class FacturaEmitidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Factura $factura)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura ' . $this->factura->numero_factura . ' - ' . config('app.name')
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.factura_emitida');
    }
}
