<?php

namespace Tests\Feature;

use App\Models\Order;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Herkomst van een bestelling: ruw bewaren wat de browser meestuurt, geen bron
 * verzinnen, en in het beheer tonen waar het label op berust (campagnelabel,
 * verwijzer, alleen klik-ID of onbekend). De browserkant staat in
 * tests/js/attribution.test.mjs.
 */
class AttributionTest extends TestCase
{
    use RefreshDatabase;

    private const SCRIPT = 'window.PMP_Attribution';

    private function bestel(array $attribution): void
    {
        Http::fake(['api.mollie.com/*' => Http::response([
            'resource' => 'payment',
            'id' => 'tr_TestHerkomst',
            'mode' => 'test',
            'status' => 'open',
            'amount' => ['value' => '10.00', 'currency' => 'EUR'],
            '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/test', 'type' => 'text/html']],
        ], 201)]);

        $pdf = new FPDF('P', 'mm', 'A4');
        for ($i = 1; $i <= 4; $i++) {
            $pdf->AddPage();
        }

        $fields = [];
        foreach ($attribution as $key => $value) {
            $fields['attribution_' . $key] = $value;
        }

        $this->postJson('/api/order', array_merge([
            'pdf' => UploadedFile::fake()->createWithContent('document.pdf', $pdf->Output('S')),
            'page_count' => 4,
            'format' => 'A4',
            'binding_type' => 'loose',
            'print_side' => 'single',
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'street' => 'Dorpsstraat',
            'number' => '12',
            'postcode' => '1234AB',
            'city' => 'Delft',
            'no_bleed_accepted' => '1',
        ], $fields))->assertOk();
    }

    private function order(array $attribution): Order
    {
        return Order::create(array_merge([
            'order_number' => 'PMP-' . strtoupper(uniqid()),
            'status' => 'paid',
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'x.pdf',
            'pdf_path' => 'pdfs/x.pdf',
            'page_count' => 4,
            'format' => 'A4',
            'binding_type' => 'loose',
            'print_side' => 'single',
            'quantity' => 1,
            'price_startup' => 1000,
            'price_pages' => 60,
            'price_binding' => 0,
            'price_shipping' => 0,
            'price_total' => 1060,
            'customer_name' => 'Jan Jansen',
            'customer_email' => 'jan@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => 'pickup',
        ], $attribution));
    }

    // --- Opslaan ---

    public function test_expliciete_utm_wordt_ruw_opgeslagen(): void
    {
        $this->bestel([
            'utm_source' => 'chatgpt.com',
            'utm_campaign' => 'najaar',
            'landing_page' => '/boekje-maken',
            'referrer' => 'https://chatgpt.com/',
        ]);

        $order = Order::sole();
        $this->assertSame('chatgpt.com', $order->utm_source);
        $this->assertNull($order->utm_medium, 'geen medium verzonnen');
        $this->assertSame('najaar', $order->utm_campaign);
        $this->assertSame('/boekje-maken', $order->landing_page);
        $this->assertSame('https://chatgpt.com/', $order->referrer);
    }

    public function test_zonder_herkomst_blijft_alles_leeg(): void
    {
        $this->bestel([]);

        $order = Order::sole();
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'fbclid', 'msclkid', 'landing_page', 'referrer'] as $column) {
            $this->assertNull($order->{$column}, $column);
        }
        $this->assertSame('onbekend', $order->attribution['basis']);
    }

    public function test_te_lange_utm_laat_bestelling_niet_mislukken(): void
    {
        $this->bestel(['utm_source' => str_repeat('a', 300), 'utm_campaign' => str_repeat('b', 300)]);

        $order = Order::sole();
        $this->assertSame(100, strlen($order->utm_source));
        $this->assertSame(255, strlen($order->utm_campaign));
    }

    public function test_instappad_alleen_als_pad_op_deze_site_zonder_querystring(): void
    {
        $this->bestel(['landing_page' => '/prijzen?email=jan@example.com', 'referrer' => 'javascript:alert(1)']);
        $order = Order::sole();
        $this->assertSame('/prijzen', $order->landing_page);
        $this->assertNull($order->referrer);

        Order::query()->delete();
        $this->bestel(['landing_page' => '//elders.example/pad']);
        $this->assertNull(Order::sole()->landing_page);
    }

    public function test_alle_klik_ids_worden_opgeslagen(): void
    {
        $this->bestel(['gclid' => 'g1', 'fbclid' => 'f1', 'msclkid' => 'm1']);

        $order = Order::sole();
        $this->assertSame(['g1', 'f1', 'm1'], [$order->gclid, $order->fbclid, $order->msclkid]);
    }

    // --- Label en bewijsbron ---

    public function test_campagnelabel_toont_de_ruwe_bron(): void
    {
        $a = $this->order(['utm_source' => 'chatgpt.com', 'utm_medium' => 'none'])->attribution;

        $this->assertSame('campagnelabel', $a['basis']);
        $this->assertSame('Campagnelabel: chatgpt.com / none', $a['label'], 'niet ucfirst, niet herschreven');
    }

    public function test_verwijzer_zonder_utm(): void
    {
        $a = $this->order(['referrer' => 'https://www.google.com/', 'landing_page' => '/pdf-laten-printen'])->attribution;

        $this->assertSame('verwijzer', $a['basis']);
        $this->assertSame('Verwijzer: www.google.com', $a['label']);
        $this->assertSame('/pdf-laten-printen', $a['landing_page']);
    }

    public function test_oude_standaardwaarde_direct_none_is_geen_bewijs(): void
    {
        $order = $this->order(['utm_source' => 'direct', 'utm_medium' => 'none', 'landing_page' => '/']);
        $a = $order->attribution;

        $this->assertSame('onbekend', $a['basis']);
        $this->assertSame('Herkomst onbekend', $a['label']);
        $this->assertStringContainsString('standaardwaarde', implode(' ', $a['notes']));
        // Ruwe waarde blijft staan en zichtbaar
        $this->assertSame('direct', $order->fresh()->utm_source);
        $this->assertSame('direct', $a['raw']['utm_source']);
    }

    public function test_oude_standaardwaarde_met_verwijzer_toont_de_verwijzer(): void
    {
        $a = $this->order(['utm_source' => 'direct', 'utm_medium' => 'none', 'referrer' => 'https://chatgpt.com/'])->attribution;

        $this->assertSame('verwijzer', $a['basis']);
        $this->assertSame('Verwijzer: chatgpt.com', $a['label']);
    }

    public function test_losse_klik_id_is_geen_betaalde_campagne(): void
    {
        $order = $this->order(['gclid' => 'abc']);
        $a = $order->attribution;

        $this->assertSame('klik-id', $a['basis']);
        $this->assertStringNotContainsString('Ads', $a['label']);
        $this->assertContains('Een klik-ID bewijst geen betaalde campagne', $a['notes']);
        $this->assertFalse($order->isFromPaidAds());
    }

    public function test_betaald_alleen_bij_expliciet_label(): void
    {
        $this->assertTrue($this->order(['utm_source' => 'google', 'utm_medium' => 'cpc'])->isFromPaidAds());
        $this->assertFalse($this->order(['utm_source' => 'google', 'utm_medium' => 'organic', 'gclid' => 'x'])->isFromPaidAds());
    }

    // --- Beheer ---

    public function test_beheer_toont_herkomst_ook_zonder_utm(): void
    {
        $this->order(['referrer' => 'https://chatgpt.com/', 'landing_page' => '/boekje-maken']);
        $this->order(['utm_source' => 'direct', 'utm_medium' => 'none', 'landing_page' => '/']);

        $html = $this->withSession(['admin_authenticated' => true])->get('/admin/orders')->assertOk()->getContent();

        $this->assertStringContainsString('Verwijzer: chatgpt.com · instap /boekje-maken', $html);
        $this->assertStringContainsString('Herkomst onbekend · instap /', $html);
        $this->assertStringNotContainsString('Direct / none', $html);
    }

    // --- Instappagina's ---

    public function test_instappaginas_leggen_herkomst_vast(): void
    {
        foreach (['/', '/boekje-maken', '/pdf-laten-printen', '/prijzen', '/over-ons', '/privacy'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, self::SCRIPT), $path);
        }
    }

    public function test_bevestigingspagina_legt_geen_herkomst_vast(): void
    {
        $order = $this->order(['mollie_payment_id' => null, 'status' => 'pending']);

        $html = $this->get('/bestelling/' . $order->order_number)->assertOk()->getContent();

        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringNotContainsString(self::SCRIPT, $html);
    }

    public function test_homepage_vult_geen_bron_meer_in(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString("|| 'direct'", $html);
        $this->assertStringNotContainsString("|| 'none'", $html);
    }
}
