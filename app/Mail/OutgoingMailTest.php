<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Message sent from «Correo y acceso» to check the outgoing mail server before switching it on.
 */
class OutgoingMailTest extends Mailable
{
    use Queueable;

    public function __construct(public string $host) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Prueba del correo del portal');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.system.test-message');
    }
}
