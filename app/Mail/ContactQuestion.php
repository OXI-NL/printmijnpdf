<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactQuestion extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $customerEmail;
    public string $question;
    public bool $isCustomerCopy;

    public function __construct(string $customerName, string $customerEmail, string $question, bool $isCustomerCopy = false)
    {
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->question = $question;
        $this->isCustomerCopy = $isCustomerCopy;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isCustomerCopy
            ? 'Bevestiging: je vraag aan PrintMijnPDF'
            : 'Nieuwe vraag van ' . $this->customerName;

        return new Envelope(
            subject: $subject,
            replyTo: [$this->customerEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-question',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
