<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
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

    /** Mockt Mollie zodat payments->get() een betaling met de gegeven status teruggeeft. */
    private function mockMollie(?int $orderId, string $status): void
    {
        $payment = Mockery::mock();
        $payment->metadata = $orderId === null ? null : (object) ['order_id' => $orderId];
        $payment->shouldReceive('isPaid')->andReturn($status === 'paid');
        $payment->shouldReceive('isCanceled')->andReturn($status === 'canceled');
        $payment->shouldReceive('isExpired')->andReturn($status === 'expired');
        $payment->shouldReceive('isFailed')->andReturn($status === 'failed');

        $endpoint = Mockery::mock();
        $endpoint->shouldReceive('get')->andReturn($payment);

        // Mollie::api() is de facade-root zelf; swap() vervangt die zonder de
        // echte client te construeren, dus er is geen API-sleutel nodig.
        $api = Mockery::mock();
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
}
