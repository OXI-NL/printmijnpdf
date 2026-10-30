<?php

namespace Tests\Feature;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmation;
use App\Mail\PaymentFailed;
use App\Models\CreditNote;
use App\Models\Order;
use App\Services\BookletImpositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mollie\Api\MollieApiClient;
use Mollie\Laravel\Facades\Mollie;
use Tests\TestCase;

/**
 * Statusovergangen van de Mollie-webhook. De Mollie-API wordt gemockt,
 * er gaat dus nooit verkeer naar buiten.
 */
class MollieWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'PMP-'.uniqid(),
            'mollie_payment_id' => 'tr_test123',
            'status' => 'pending',
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'stored.pdf',
            'pdf_path' => 'pdfs/stored.pdf',
            'page_count' => 20,
            'format' => 'A4',
            'binding_type' => 'loose', // voorkomt impositie op een niet-bestaand bestand
            'print_side' => 'double',
            'quantity' => 1,
            'price_startup' => 1000,
            'price_pages' => 300,
            'price_binding' => 0,
            'price_shipping' => 675,
            'price_total' => 1975,
            'customer_name' => 'Jan Jansen',
            'customer_email' => 'jan@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ], $overrides));
    }

    /** Mockt Mollie zodat payments->get() een betaling met de gegeven status (en terugbetalingen) teruggeeft. */
    private function mockMollie(?int $orderId, string $status, array $refunds = []): void
    {
        $payment = Mockery::mock();
        $payment->metadata = $orderId === null ? null : (object) ['order_id' => $orderId];
        $payment->shouldReceive('isPaid')->andReturn($status === 'paid');
        $payment->shouldReceive('isCanceled')->andReturn($status === 'canceled');
        $payment->shouldReceive('isExpired')->andReturn($status === 'expired');
        $payment->shouldReceive('isFailed')->andReturn($status === 'failed');
        $payment->shouldReceive('hasRefunds')->andReturn($refunds !== []);
        $payment->shouldReceive('refunds')->andReturn($refunds);

        $endpoint = Mockery::mock();
        $endpoint->shouldReceive('get')->andReturn($payment);

        // Mollie::api() is de facade-root zelf; swap() vervangt die zonder de
        // echte client te construeren, dus er is geen API-sleutel nodig.
        // Sinds laravel-mollie 4.1.2 heeft api() het return type MollieApiClient,
        // dus de mock moet van die class zijn.
        $api = Mockery::mock(MollieApiClient::class);
        $api->payments = $endpoint;

        Mollie::swap($api);
    }

    public function test_betaling_zet_bestelling_op_paid(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_geannuleerde_betaling_zet_bestelling_op_cancelled(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'canceled');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame('cancelled', $order->refresh()->status);
    }

    public function test_verlopen_betaling_zet_bestelling_op_cancelled(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'expired');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame('cancelled', $order->refresh()->status);
    }

    public function test_mislukte_betaling_zet_bestelling_op_cancelled(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'failed');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame('cancelled', $order->refresh()->status);
    }

    public function test_reeds_betaalde_bestelling_wordt_niet_teruggezet(): void
    {
        $order = $this->order(['status' => 'paid']);
        $this->mockMollie($order->id, 'canceled');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame('paid', $order->refresh()->status, 'een betaalde order mag nooit alsnog cancelled worden');
    }

    public function test_webhook_zonder_payment_id_geeft_400(): void
    {
        $this->post('/webhook/mollie', [])->assertStatus(400);
    }

    public function test_webhook_voor_onbekende_bestelling_geeft_ok(): void
    {
        $this->mockMollie(999999, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();
    }

    public function test_webhook_zonder_order_id_in_metadata_geeft_ok(): void
    {
        $this->mockMollie(null, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();
    }

    public function test_betaling_stuurt_bevestiging_en_adminmelding(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        Mail::assertSent(OrderConfirmation::class, 1);
        Mail::assertSent(AdminOrderNotification::class, 1);
        $this->assertNotNull($order->refresh()->paid_processed_at);
    }

    public function test_mails_ook_als_bedankpagina_de_webhook_voor_was(): void
    {
        // complete() zette de order al op 'paid' voordat de webhook binnenkwam
        $order = $this->order(['status' => 'paid', 'paid_at' => now()]);
        $this->mockMollie($order->id, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        Mail::assertSent(OrderConfirmation::class, 1);
        Mail::assertSent(AdminOrderNotification::class, 1);
    }

    public function test_mails_gaan_weg_voordat_de_inslag_begint(): void
    {
        // Een zware inslag kan fataal aflopen (geheugen, time-out); dan moeten
        // klant en admin hun mail al hebben, want een retry komt er niet
        $order = $this->order(['binding_type' => 'booklet']);
        $this->mockMollie($order->id, 'paid');

        // Assertions binnen de inslag zou de catch in de controller opslokken,
        // dus noteer wat er op dat moment al verstuurd was
        $sentBeforeImposition = null;
        $imposition = Mockery::mock(BookletImpositionService::class);
        $imposition->shouldReceive('createImposition')->once()->andReturnUsing(function () use (&$sentBeforeImposition) {
            $sentBeforeImposition = [
                Mail::sent(OrderConfirmation::class)->count(),
                Mail::sent(AdminOrderNotification::class)->count(),
            ];

            return ['success' => true, 'path' => 'pdfs/imposed/x.pdf', 'message' => ''];
        });
        $this->app->instance(BookletImpositionService::class, $imposition);

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame([1, 1], $sentBeforeImposition, 'bevestiging en adminmelding horen vóór de inslag te zijn verstuurd');
    }

    public function test_herhaalde_webhook_mailt_maar_een_keer(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();
        Cache::flush(); // de cache-dedupe mag niet de enige bescherming zijn
        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        Mail::assertSent(OrderConfirmation::class, 1);
        Mail::assertSent(AdminOrderNotification::class, 1);
    }

    public function test_reeds_afgehandelde_order_wordt_niet_opnieuw_gemaild(): void
    {
        // Oude orders krijgen paid_processed_at via de migratie; een latere
        // webhook (bv. bij terugbetaling) mag de klant niet lastigvallen
        $order = $this->order(['status' => 'shipped', 'paid_processed_at' => now()->subWeeks(2)]);
        $this->mockMollie($order->id, 'paid');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_mislukte_betaling_mailt_de_klant_een_keer(): void
    {
        $order = $this->order();
        $this->mockMollie($order->id, 'expired');

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();
        Cache::flush(); // bv. een cache:clear bij een deploy
        $this->get('/bestelling/'.$order->order_number);
        $this->get('/bestelling/'.$order->order_number);

        Mail::assertSent(PaymentFailed::class, 1);
        $this->assertNotNull($order->refresh()->payment_failed_mailed_at);
    }

    public function test_oude_geannuleerde_order_krijgt_geen_mislukt_mail(): void
    {
        $order = $this->order(['status' => 'cancelled', 'payment_failed_mailed_at' => now()->subWeeks(2)]);
        $this->mockMollie($order->id, 'expired');

        $this->get('/bestelling/'.$order->order_number);

        Mail::assertNothingSent();
    }

    private function refund(string $id, string $value, string $status = 'pending'): object
    {
        return (object) [
            'id' => $id,
            'status' => $status,
            'amount' => (object) ['value' => $value, 'currency' => 'EUR'],
            'createdAt' => '2026-10-28T14:00:00+00:00',
            'description' => 'Drukfout vorige levering',
        ];
    }

    public function test_terugbetaling_maakt_creditnota_en_order_loopt_door(): void
    {
        Storage::fake('local');
        $order = $this->order(['status' => 'paid', 'paid_at' => now(), 'paid_processed_at' => now()]);
        $this->mockMollie($order->id, 'paid', [$this->refund('re_1', '19.75')]);

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status, 'terugbetaald betekent niet: niet verzenden');
        $creditNote = $order->creditNotes()->sole();
        $this->assertSame('C-PMP-261028-0001', $creditNote->credit_number);
        $this->assertSame(1975, $creditNote->amount_incl_btw);
        $this->assertSame(1632, $creditNote->amount_excl_btw);
        $this->assertSame(343, $creditNote->amount_btw);
        $this->assertSame(1975, $order->refunded_amount);
        Storage::disk('local')->assertExists($creditNote->pdf_path);
        Mail::assertNothingSent();
    }

    public function test_herhaalde_webhook_maakt_geen_tweede_creditnota(): void
    {
        Storage::fake('local');
        $order = $this->order(['status' => 'shipped', 'paid_at' => now(), 'paid_processed_at' => now()]);
        $this->mockMollie($order->id, 'paid', [$this->refund('re_1', '5.00')]);

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();
        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame(1, CreditNote::count());
    }

    public function test_tweede_gedeeltelijke_terugbetaling_krijgt_volgend_nummer(): void
    {
        Storage::fake('local');
        $order = $this->order(['status' => 'paid', 'paid_at' => now(), 'paid_processed_at' => now()]);
        $this->mockMollie($order->id, 'paid', [$this->refund('re_1', '6.75'), $this->refund('re_2', '3.00', 'refunded')]);

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame(['C-PMP-261028-0001', 'C-PMP-261028-0002'], CreditNote::orderBy('id')->pluck('credit_number')->all());
        $this->assertSame(975, $order->refresh()->refunded_amount);
    }

    public function test_mislukte_of_wachtende_terugbetaling_telt_niet(): void
    {
        Storage::fake('local');
        $order = $this->order(['status' => 'paid', 'paid_at' => now(), 'paid_processed_at' => now()]);
        $this->mockMollie($order->id, 'paid', [
            $this->refund('re_q', '5.00', 'queued'),
            $this->refund('re_f', '5.00', 'failed'),
            $this->refund('re_c', '5.00', 'canceled'),
        ]);

        $this->post('/webhook/mollie', ['id' => 'tr_test123'])->assertOk();

        $this->assertSame(0, CreditNote::count());
    }

    public function test_sync_commando_maakt_ontbrekende_creditnotas(): void
    {
        Storage::fake('local');
        $order = $this->order(['status' => 'shipped', 'paid_at' => now()->subWeek(), 'paid_processed_at' => now()]);
        $this->mockMollie($order->id, 'paid', [$this->refund('re_1', '19.75')]);

        $this->artisan('mollie:sync-refunds', ['--order' => $order->order_number])->assertSuccessful();
        $this->artisan('mollie:sync-refunds')->assertSuccessful();

        $this->assertSame(1, CreditNote::count());
        $this->assertSame('shipped', $order->refresh()->status);
    }
}
