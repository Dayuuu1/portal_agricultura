<?php

namespace App\Mail\MesaPartes;

use App\Models\CitizenNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail copy of a message left in the citizen's inbox (observations, answers, derivations…).
 */
class SubmissionNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CitizenNotification $notification) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notification->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.mesa-partes.notice');
    }
}
