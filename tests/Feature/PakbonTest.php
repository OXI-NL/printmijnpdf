<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\PakbonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** De pakbon toont afhalen duidelijk en schrijft namen met accenten goed */
class PakbonTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $delivery): Order
    {
        return Order::create([
            'order_number' => 'PMP-'.uniqid(),
            'status' => 'paid',
            'pdf_original_name' => 'café-menu.pdf',
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
            'customer_name' => 'Aléxia Silva',
            'customer_email' => 'alexia@example.com',
            'address_street' => 'Zaagmolenweg',
            'address_number' => '62',
            'address_postcode' => '5741LV',
            'address_city' => 'Beek en Donk',
            'delivery_type' => $delivery,
        ]);
    }

    /** Tekst uit de ongecomprimeerde content stream halen */
    private function pdfText(Order $order): string
    {
        $pdf = PakbonService::generate($order);
        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $m);

        return implode("\n", array_map(fn ($s) => @gzuncompress($s) ?: $s, $m[1]));
    }

    public function test_afhaalorder_toont_geen_afleveradres(): void
    {
        $text = $this->pdfText($this->order('pickup'));

        $this->assertStringContainsString('WORDT AFGEHAALD', $text);
        $this->assertStringContainsString('AFHAALBON', $text);
        $this->assertStringNotContainsString('AFLEVERADRES', $text);
        $this->assertStringNotContainsString('Zaagmolenweg', $text);
    }

    public function test_verzendorder_toont_afleveradres(): void
    {
        $text = $this->pdfText($this->order('shipping'));

        $this->assertStringContainsString('AFLEVERADRES', $text);
        $this->assertStringContainsString('Zaagmolenweg', $text);
        $this->assertStringNotContainsString('AFGEHAALD', $text);
    }

    public function test_naam_met_accent_wordt_als_windows1252_geschreven(): void
    {
        $text = $this->pdfText($this->order('pickup'));

        $this->assertStringContainsString("Al\xE9xia Silva", $text);
        $this->assertStringNotContainsString("Al\xC3\xA9xia", $text);
    }
}
