<?php

namespace App\Mail\MesaPartes;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the mesa de partes that a document arrived or an observation was completed and waits for review.
 */
class StaffSubmissionAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission, public bool $isCure = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->isCure ? 'Subsanación recibida: ' : 'Nuevo documento en mesa de partes: ').$this->submission->code);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.mesa-partes.staff-alert');
    }
}
