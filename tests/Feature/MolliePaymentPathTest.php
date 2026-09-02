<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Mollie\Laravel\Facades\Mollie;
use Tests\TestCase;

/**
 * Betaalpad door de echte HTTP-laag.
 *
 * De webhooktests mocken Mollie volledig weg en raken Guzzle dus niet. Deze
 * tests lopen wel door MollieLaravelHttpClientAdapter, Laravels HTTP-client en
 * de PSR-7 objecten van guzzlehttp/psr7 heen. Ze vangen daarmee breuken in
 * createPsrRequest(), het doorgeven van de body-stream en toPsrResponse() --
 * precies de raakvlakken die een Guzzle- of psr7-major kan breken.
 */
class MolliePaymentPathTest extends TestCase
{
    private function betaling(array $overrides = []): array
    {
        return array_merge([
            'resource' => 'payment',
            'id' => 'tr_TestPayment01',
            'mode' => 'test',
            'createdAt' => '2026-09-02T10:00:00+00:00',
            'amount' => ['value' => '36.75', 'currency' => 'EUR'],
            'description' => 'PrintMijnPDF bestelling PMP-TEST',
            'metadata' => ['order_id' => 42],
            'status' => 'open',
            'profileId' => 'pfl_test',
            'sequenceType' => 'oneoff',
            'redirectUrl' => 'https://printmijnpdf.nl/bestelling/PMP-TEST',
            'webhookUrl' => 'https://printmijnpdf.nl/webhook/mollie',
            '_links' => [
                'self' => ['href' => 'https://api.mollie.com/v2/payments/tr_TestPayment01', 'type' => 'application/hal+json'],
                'checkout' => ['href' => 'https://www.mollie.com/checkout/select-method/testpayment', 'type' => 'text/html'],
            ],
        ], $overrides);
    }

    public function test_betaling_aanmaken_gaat_door_de_http_laag_en_levert_een_checkout_url(): void
    {
        Http::fake([
            'api.mollie.com/*' => Http::response($this->betaling(), 201),
        ]);

        $payment = Mollie::api()->payments->create([
            'amount' => ['currency' => 'EUR', 'value' => '36.75'],
            'description' => 'PrintMijnPDF bestelling PMP-TEST',
            'redirectUrl' => 'https://printmijnpdf.nl/bestelling/PMP-TEST',
            'webhookUrl' => 'https://printmijnpdf.nl/webhook/mollie',
            'metadata' => ['order_id' => 42],
        ]);

        $this->assertSame('tr_TestPayment01', $payment->id);
        $this->assertSame('open', $payment->status);
        $this->assertSame(
            'https://www.mollie.com/checkout/select-method/testpayment',
            $payment->getCheckoutUrl(),
            'zonder checkout-URL kan de klant niet afrekenen',
        );
    }

    public function test_verzoek_bevat_de_payload_als_json_body(): void
    {
        Http::fake(['api.mollie.com/*' => Http::response($this->betaling(), 201)]);

        Mollie::api()->payments->create([
            'amount' => ['currency' => 'EUR', 'value' => '36.75'],
            'description' => 'PrintMijnPDF bestelling PMP-TEST',
            'redirectUrl' => 'https://printmijnpdf.nl/bestelling/PMP-TEST',
            'metadata' => ['order_id' => 42],
        ]);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $request->method() === 'POST'
                && str_contains($request->url(), 'payments')
                && ($body['amount']['value'] ?? null) === '36.75'
                && ($body['metadata']['order_id'] ?? null) === 42;
        });
    }

    public function test_betaling_ophalen_leest_status_en_metadata_terug(): void
    {
        // Mollie stuurt bij een betaalde transactie ook paidAt mee; isPaid()
        // kijkt naar dat veld en niet naar status.
        Http::fake([
            'api.mollie.com/*' => Http::response($this->betaling([
                'status' => 'paid',
                'paidAt' => '2026-09-02T10:05:00+00:00',
            ]), 200),
        ]);

        $payment = Mollie::api()->payments->get('tr_TestPayment01');

        $this->assertSame('paid', $payment->status);
        $this->assertTrue($payment->isPaid(), 'isPaid() bepaalt in de webhook of een order op paid gaat');
        $this->assertSame(42, $payment->metadata->order_id);
    }

    public function test_api_fout_wordt_een_apiexception(): void
    {
        Http::fake([
            'api.mollie.com/*' => Http::response([
                'status' => 422,
                'title' => 'Unprocessable Entity',
                'detail' => 'The amount is lower than the minimum',
            ], 422),
        ]);

        $this->expectException(\Mollie\Api\Exceptions\ApiException::class);

        Mollie::api()->payments->create([
            'amount' => ['currency' => 'EUR', 'value' => '0.01'],
            'description' => 'te laag',
            'redirectUrl' => 'https://printmijnpdf.nl/x',
        ]);
    }
}
