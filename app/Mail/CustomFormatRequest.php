<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomFormatRequest extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $customerEmail;
    public string $customerPhone;
    public bool $isCustomerCopy;
    private ?string $pdfPath;
    private ?string $pdfOriginalName;

    public function __construct(
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        bool $isCustomerCopy = false,
        ?string $pdfPath = null,
        ?string $pdfOriginalName = null,
    ) {
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->customerPhone = $customerPhone;
        $this->isCustomerCopy = $isCustomerCopy;
        $this->pdfPath = $pdfPath;
        $this->pdfOriginalName = $pdfOriginalName;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isCustomerCopy
            ? 'Bevestiging: je aanvraag voor een afwijkend formaat'
            : 'Nieuwe aanvraag afwijkend formaat van ' . $this->customerName;

        return new Envelope(
            subject: $subject,
            replyTo: [$this->customerEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.custom-format-request',
        );
    }

    public function attachments(): array
    {
        if ($this->pdfPath && !$this->isCustomerCopy) {
            return [
                Attachment::fromStorage($this->pdfPath)
                    ->as($this->pdfOriginalName ?? 'document.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
