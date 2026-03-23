<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\PakbonService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        return [];
    }

    /**
     * Build the message met pakbon PDF als bijlage
     */
    public function build()
    {
        try {
            $pdfData = PakbonService::generate($this->order);
            $filename = PakbonService::filename($this->order);

            $this->attachData($pdfData, $filename, [
                'mime' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error("Kon pakbon niet genereren voor order {$this->order->order_number}: " . $e->getMessage());
        }

        return $this;
    }
}
