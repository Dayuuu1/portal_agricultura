<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notice to the entity (complaints mailbox) with the full claim and its attachments.
 */
class ComplaintRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: filled($this->complaint->email) ? [new Address($this->complaint->email, $this->complaint->full_name)] : [],
            subject: "Libro de Reclamaciones: nuevo reclamo {$this->complaint->code}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.complaints.registered');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->complaint->attachments ?? [])
            ->map(fn (array $file): Attachment => Attachment::fromStorageDisk('local', $file['path'])->as($file['name']))
            ->all();
    }
}
