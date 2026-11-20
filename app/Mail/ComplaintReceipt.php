<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Copy of the registered claim for the citizen (DS N° 007-2020-PCM, art. 15.6).
 */
class ComplaintReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Constancia de su reclamo {$this->complaint->code}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.complaints.receipt');
    }
}
