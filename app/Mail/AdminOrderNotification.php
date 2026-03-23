<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\PakbonService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuwe bestelling: ' . $this->order->order_number . ' - ' . $this->order->formatted_total,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-order-notification',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => PakbonService::generate($this->order),
                PakbonService::filename($this->order)
            )->withMime('application/pdf'),
        ];
    }
}
