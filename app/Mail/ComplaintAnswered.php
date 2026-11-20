<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Answer to the claim, sent when the person authorized e-mail notification (art. 17.1).
 */
class ComplaintAnswered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Respuesta a su reclamo {$this->complaint->code}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.complaints.answered');
    }
}
