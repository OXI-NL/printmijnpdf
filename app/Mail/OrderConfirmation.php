<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\OrderThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bestelling bevestigd - ' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $thumbnail = OrderThumbnailService::existingPath($this->order);

        return new Content(
            view: 'emails.order-confirmation',
            // Absoluut pad: de view embedt het bestand inline (CID), zonder externe URL
            with: ['thumbnailPath' => $thumbnail ? Storage::disk('local')->path($thumbnail) : null],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
