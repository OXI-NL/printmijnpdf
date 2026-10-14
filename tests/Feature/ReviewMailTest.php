<?php

namespace Tests\Feature;

use App\Mail\OrderReadyForPickup;
use App\Mail\OrderShipped;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** De verzend- en afhaalmail vragen om een Google-review */
class ReviewMailTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $delivery): Order
    {
        return Order::create([
            'order_number' => 'PMP-'.uniqid(),
            'status' => 'shipped',
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'stored.pdf',
            'pdf_path' => 'pdfs/stored.pdf',
            'page_count' => 8,
            'format' => 'A5',
            'binding_type' => 'booklet',
            'print_side' => 'double',
            'quantity' => 1,
            'price_startup' => 1000,
            'price_pages' => 80,
            'price_binding' => 500,
            'price_shipping' => $delivery === 'shipping' ? 750 : 0,
            'price_total' => 2330,
            'customer_name' => 'Jan Jansen',
            'customer_email' => 'jan@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => $delivery,
            'shipped_at' => now(),
        ]);
    }

    public function test_verzendmail_bevat_de_reviewlink(): void
    {
        $html = (new OrderShipped($this->order('shipping')))->render();
        $this->assertStringContainsString(config('seo.google_review'), $html);
    }

    public function test_afhaalmail_bevat_de_reviewlink(): void
    {
        $html = (new OrderReadyForPickup($this->order('pickup')))->render();
        $this->assertStringContainsString(config('seo.google_review'), $html);
    }
}
