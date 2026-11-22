<?php

namespace App\Mail\MesaPartes;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Copy of the cargo de recepción for the citizen: number, date and time, filing date and files received.
 */
class SubmissionReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cargo de recepción {$this->submission->code}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.mesa-partes.receipt');
    }
}
