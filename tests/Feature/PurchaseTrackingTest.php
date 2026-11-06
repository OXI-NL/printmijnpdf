<?php

namespace Tests\Feature;

use App\Models\CreditNote;
use App\Models\Order;
use App\Services\PurchaseTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mollie\Api\MollieApiClient;
use Mollie\Laravel\Facades\Mollie;
use Tests\TestCase;

/**
 * Het GA4-aankoopevent: één keer per betaalde order, ongeacht herladen,
 * terugkeren of een tweede browser. Geen echte betaling, geen verkeer naar
 * Mollie of Google: Mollie wordt gemockt en het event staat alleen in de HTML.
 */
class PurchaseTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const PURCHASE = "gtag('event', 'purchase'";

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'PMP-TEST-'.strtoupper(substr(uniqid(), -6)),
            'mollie_payment_id' => null, // geen Mollie-aanroep in complete()
            'status' => 'paid',
            'paid_at' => now(),
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'stored.pdf',
            'pdf_path' => 'pdfs/stored.pdf',
            'page_count' => 20,
            'format' => 'A4',
            'binding_type' => 'loose',
            'print_side' => 'double',
            'quantity' => 1,
            'price_startup' => 1000,
            'price_pages' => 300,
            'price_binding' => 0,
            'price_shipping' => 675,
            'price_total' => 1975,
            'customer_name' => 'Test Klant',
            'customer_email' => 'test@example.com',
            'address_street' => 'Teststraat',
            'address_number' => '1',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ], $overrides));
    }

    private function confirm(Order $order, ?string $token = null)
    {
        return $this->postJson('/bestelling/'.$order->order_number.'/aankoop-gemeten', [
            'token' => $token ?? PurchaseTracking::token($order),
        ]);
    }

    private function mockMollie(Order $order, string $status, string $mode = 'live'): void
    {
        $payment = Mockery::mock();
        $payment->metadata = (object) ['order_id' => $order->id];
        $payment->mode = $mode;
        $payment->shouldReceive('isPaid')->andReturn($status === 'paid');
        $payment->shouldReceive('isCanceled')->andReturn($status === 'canceled');
        $payment->shouldReceive('isExpired')->andReturn(false);
        $payment->shouldReceive('isFailed')->andReturn(false);
        $payment->shouldReceive('hasRefunds')->andReturn(false);
        $payment->shouldReceive('refunds')->andReturn([]);

        $endpoint = Mockery::mock();
        $endpoint->shouldReceive('get')->andReturn($payment);
        $api = Mockery::mock(MollieApiClient::class);
        $api->payments = $endpoint;
        Mollie::swap($api);
    }

    public function test_betaalde_order_stuurt_aankoop_met_juist_bedrag_en_id(): void
    {
        $order = $this->order(['promo_code' => 'PMPWINTER']);

        $html = $this->get('/bestelling/'.$order->order_number)->assertOk()->getContent();

        $this->assertStringContainsString(self::PURCHASE, $html);
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $html);
        $this->assertStringContainsString('"currency":"EUR"', $html);
        $this->assertStringContainsString('"value":19.75', $html);
        $this->assertStringContainsString('"shipping":6.75', $html);
        $this->assertStringContainsString('"coupon":"PMPWINTER"', $html);
        // Geen klantgegevens in het event (de pagina zelf toont ze wel aan de klant)
        $script = substr($html, strpos($html, self::PURCHASE) - 1200, 1400);
        $this->assertStringNotContainsString('test@example.com', $script);
        $this->assertStringNotContainsString('Test Klant', $script);
        $this->assertStringNotContainsString('Teststraat', $script);
    }

    public function test_herladen_of_tweede_browser_telt_niet_opnieuw(): void
    {
        $order = $this->order();

        $this->assertStringContainsString(self::PURCHASE, $this->get('/bestelling/'.$order->order_number)->getContent());
        // Bijv. de tab van Mollie én de browser waarin de bank-app terugkeert
        $this->assertStringNotContainsString(self::PURCHASE, $this->get('/bestelling/'.$order->order_number)->getContent());
    }

    public function test_na_bevestiging_nooit_meer_een_aankoop(): void
    {
        $order = $this->order();
        $this->get('/bestelling/'.$order->order_number);

        $this->confirm($order)->assertOk()->assertJson(['recorded' => true]);
        $this->confirm($order)->assertOk()->assertJson(['recorded' => false]); // herhaalde aflevering

        $this->travel(30)->days();
        $this->assertStringNotContainsString(self::PURCHASE, $this->get('/bestelling/'.$order->order_number)->getContent());
        $this->assertNotNull($order->refresh()->analytics_purchase_sent_at);
    }

    public function test_zonder_bevestiging_probeert_een_later_bezoek_het_opnieuw(): void
    {
        // gtag geblokkeerd of netwerkfout: geen permanente vlag, dus herstel mogelijk
        $order = $this->order();
        $this->get('/bestelling/'.$order->order_number);

        $this->travel(5)->minutes();
        $this->assertStringNotContainsString(self::PURCHASE, $this->get('/bestelling/'.$order->order_number)->getContent());

        $this->travel(6)->minutes(); // claim van 10 minuten verlopen
        $this->assertStringContainsString(self::PURCHASE, $this->get('/bestelling/'.$order->order_number)->getContent());
    }

    public function test_onbetaald_of_mislukt_is_geen_aankoop(): void
    {
        $pending = $this->order(['status' => 'pending', 'paid_at' => null]);
        $cancelled = $this->order(['status' => 'cancelled', 'paid_at' => null, 'payment_failed_mailed_at' => now()]);

        $html = $this->get('/bestelling/'.$pending->order_number)->getContent();
        $this->assertStringNotContainsString(self::PURCHASE, $html);
        $this->assertStringContainsString('payment_pending', $html);

        $html = $this->get('/bestelling/'.$cancelled->order_number)->getContent();
        $this->assertStringNotContainsString(self::PURCHASE, $html);
        $this->assertStringContainsString('payment_failed', $html);

        // Ook een geldig token maakt van een onbetaalde order geen aankoop
        $this->confirm($pending)->assertJson(['recorded' => false]);
        $this->assertNull($pending->refresh()->analytics_purchase_sent_at);
    }

    public function test_verkeerd_token_wordt_genegeerd(): void
    {
        $order = $this->order();
        $this->get('/bestelling/'.$order->order_number);

        $this->confirm($order, 'vervalst')->assertJson(['recorded' => false]);
        $this->assertNull($order->refresh()->analytics_purchase_sent_at);
    }

    public function test_twee_orders_blijven_twee_aankopen(): void
    {
        $a = $this->order();
        $b = $this->order();

        $this->assertStringContainsString('"transaction_id":"'.$a->order_number.'"', $this->get('/bestelling/'.$a->order_number)->getContent());
        $this->assertStringContainsString('"transaction_id":"'.$b->order_number.'"', $this->get('/bestelling/'.$b->order_number)->getContent());
    }

    public function test_terugkeer_van_mollie_zet_betaald_en_meet_direct(): void
    {
        $order = $this->order(['status' => 'pending', 'paid_at' => null, 'mollie_payment_id' => 'tr_test123']);
        $this->mockMollie($order, 'paid');

        $html = $this->get('/bestelling/'.$order->order_number)->getContent();

        $this->assertStringContainsString(self::PURCHASE, $html);
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('live', $order->payment_mode);
    }

    public function test_testbetaling_gaat_als_debug_event(): void
    {
        $order = $this->order(['status' => 'pending', 'paid_at' => null, 'mollie_payment_id' => 'tr_test123']);
        $this->mockMollie($order, 'paid', 'test');

        $html = $this->get('/bestelling/'.$order->order_number)->getContent();

        $this->assertSame('test', $order->refresh()->payment_mode);
        $this->assertStringContainsString('debug_mode: true', $html);
    }

    public function test_webhook_legt_betaalmodus_vast(): void
    {
        $order = $this->order(['status' => 'pending', 'paid_at' => null, 'mollie_payment_id' => 'tr_test123']);
        $this->mockMollie($order, 'paid', 'test');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame('test', $order->refresh()->payment_mode);
    }

    public function test_productie_stuurt_geen_debug_mode(): void
    {
        config(['analytics.debug' => false]);
        $order = $this->order();

        $html = $this->get('/bestelling/'.$order->order_number)->getContent();

        $this->assertStringContainsString("gtag('config', \"G-9F48GD4CX5\");", $html);
        $this->assertStringNotContainsString('debug_mode', $html);
    }

    public function test_migratie_markeert_bestaande_betaalde_orders_als_gemeten(): void
    {
        $migration = require database_path('migrations/2024_01_17_000001_add_analytics_purchase_to_orders_table.php');
        $migration->down();
        $migration->up();

        $paid = $this->order(['status' => 'shipped']);
        $pending = $this->order(['status' => 'pending', 'paid_at' => null]);
        $migration->down();
        $migration->up();

        $this->assertNotNull(Order::find($paid->id)->analytics_purchase_sent_at);
        $this->assertNull(Order::find($pending->id)->analytics_purchase_sent_at);
        // Een oude klant die zijn bevestigingspagina opent, telt niet opnieuw
        $this->assertStringNotContainsString(self::PURCHASE, $this->get('/bestelling/'.$paid->order_number)->getContent());
    }

    public function test_orderrapport_telt_unieke_betaalde_orders_en_terugbetalingen(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $a = $this->order();
        $a->forceFill(['analytics_purchase_sent_at' => now()])->save();
        $this->order(['price_total' => 2161]);
        $this->order(['status' => 'pending', 'paid_at' => null]);
        $this->order(['payment_mode' => 'test']);
        CreditNote::create([
            'order_id' => $a->id,
            'credit_number' => 'C-PMP-261103-0001',
            'mollie_refund_id' => 're_test1',
            'credit_date' => '2026-11-04',
            'amount_excl_btw' => 1533,
            'amount_btw' => 322,
            'amount_incl_btw' => 1855,
        ]);

        $this->artisan('orders:report', ['--from' => '2026-11-01', '--to' => '2026-11-05'])
            ->expectsOutputToContain('Orderadministratie 2026-11-01 t/m 2026-11-05')
            ->expectsTable(['Maatstaf', 'Waarde'], [
                ['Unieke betaalde orders', 2],
                ['Bruto orderbedrag incl. btw', '€ 41,36'],
                ['Terugbetaald in deze periode', '€ 18,55 (1×)'],
                ['Netto na terugbetaling', '€ 22,81'],
                ['Gem. orderwaarde (bruto)', '€ 20,68'],
                ['Waarvan aankoop in GA4 bevestigd', '1 van 2'],
                ['Testbetalingen (niet meegeteld)', 1],
            ])
            ->assertSuccessful();
    }
}
