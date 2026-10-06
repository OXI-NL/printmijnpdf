<?php

namespace Tests\Feature;

use App\Models\Order;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bestelling plaatsen: de server telt zelf pagina's en formaat, en een boekje
 * wordt afgerond op een veelvoud van 4 met blanco pagina's aan het eind.
 * Mollie wordt via Http::fake() beantwoord.
 */
class OrderStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.mollie.com/*' => Http::response([
            'resource' => 'payment',
            'id' => 'tr_TestStore01',
            'mode' => 'test',
            'status' => 'open',
            'amount' => ['value' => '10.00', 'currency' => 'EUR'],
            '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/test', 'type' => 'text/html']],
        ], 201)]);
    }

    private function pdf(int $pages, string $format = 'A4'): UploadedFile
    {
        $pdf = new FPDF('P', 'mm', $format);
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage();
        }

        return UploadedFile::fake()->createWithContent('document.pdf', $pdf->Output('S'));
    }

    private function bestel(array $overrides = [])
    {
        return $this->postJson('/api/order', array_merge([
            'pdf' => $this->pdf(10),
            'page_count' => 10,
            'format' => 'A4',
            'binding_type' => 'booklet',
            'print_side' => 'double',
            'quantity' => 1,
            'delivery_type' => 'shipping',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'street' => 'Dorpsstraat',
            'number' => '12',
            'postcode' => '1234AB',
            'city' => 'Delft',
        ], $overrides));
    }

    public function test_boekje_van_10_paginas_vereist_bevestiging(): void
    {
        $this->bestel()
            ->assertStatus(422)
            ->assertJsonValidationErrors('blank_pages_accepted');

        $this->assertSame(0, Order::count());
    }

    public function test_boekje_van_10_paginas_wordt_12_met_2_blanco(): void
    {
        $this->bestel(['blank_pages_accepted' => '1'])->assertOk()->assertJson(['success' => true]);

        $order = Order::sole();
        $this->assertSame(10, $order->page_count);
        $this->assertSame(2, $order->blank_pages);
        $this->assertSame('10 + 2 blanco', $order->pages_label);
        $this->assertSame(12 * config('pricing.per_page_a4'), $order->price_pages);
    }

    public function test_boekje_met_veelvoud_van_vier_heeft_geen_bevestiging_nodig(): void
    {
        $this->bestel(['pdf' => $this->pdf(12), 'page_count' => 12])->assertOk();

        $this->assertSame(0, Order::sole()->blank_pages);
    }

    public function test_losse_paginas_worden_niet_aangevuld(): void
    {
        $this->bestel(['binding_type' => 'loose'])->assertOk();

        $order = Order::sole();
        $this->assertSame(0, $order->blank_pages);
        $this->assertSame(10 * config('pricing.per_page_a4'), $order->price_pages);
    }

    public function test_paginatelling_uit_de_browser_wordt_genegeerd(): void
    {
        $this->bestel(['page_count' => 1, 'blank_pages_accepted' => '1'])->assertOk();

        $order = Order::sole();
        $this->assertSame(10, $order->page_count, 'de server telt zelf');
        $this->assertSame(12 * config('pricing.per_page_a4'), $order->price_pages);
    }

    public function test_formaat_uit_de_browser_wordt_genegeerd(): void
    {
        // A4-PDF opgegeven als (goedkoper) A5
        $this->bestel(['format' => 'A5', 'blank_pages_accepted' => '1'])->assertOk();

        $this->assertSame('A4', Order::sole()->format);
    }

    public function test_boekje_van_meer_dan_64_paginas_wordt_geweigerd(): void
    {
        $this->bestel(['pdf' => $this->pdf(65), 'blank_pages_accepted' => '1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('binding_type');
    }
}
