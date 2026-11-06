<?php

namespace Tests\Feature;

use App\Models\CreditNote;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\MonthlyInvoiceSummaryService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Mockery;
use Mollie\Api\MollieApiClient;
use Tests\TestCase;

/**
 * Mollie-transactiekosten per order uit de Balances API. De Mollie-client wordt
 * gemockt, er gaat dus nooit verkeer naar buiten.
 */
class MollieFeeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        config(['mollie_fees.org_token' => 'access_dummytokenvoortestsalleen000000', 'mollie_fees.vat_included' => true, 'mollie_fees.vat_percentage' => 21]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'PMP-261028-2627',
            'mollie_payment_id' => 'tr_betaald1',
            'status' => 'paid',
            'paid_at' => '2026-10-28 10:00:00',
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
            'customer_name' => 'Jan Jansen',
            'customer_email' => 'jan@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ], $overrides));
    }

    /** Een balance transaction zoals de Balances API die teruggeeft. */
    private function transaction(string $paymentId, string $amount, string $deduction, string $createdAt = '2026-10-28T10:01:00+00:00', bool $nestedContext = false): object
    {
        $result = number_format((round((float) $amount * 100) - round(abs((float) $deduction) * 100)) / 100, 2, '.', '');

        return (object) [
            'resource' => 'balance_transaction',
            'id' => 'baltr_'.substr(md5($paymentId), 0, 10),
            'type' => 'payment',
            'createdAt' => $createdAt,
            'initialAmount' => (object) ['currency' => 'EUR', 'value' => $amount],
            'deductions' => (object) ['currency' => 'EUR', 'value' => $deduction],
            'resultAmount' => (object) ['currency' => 'EUR', 'value' => $result],
            'context' => $nestedContext
                ? (object) ['payment' => (object) ['paymentId' => $paymentId]]
                : (object) ['paymentId' => $paymentId],
        ];
    }

    /** Mockt de organization-client zodat balanceTransactions->iteratorForPrimary() deze transacties geeft (nieuwste eerst). */
    private function mockBalance(array $transactions, ?int $times = null): void
    {
        $endpoint = Mockery::mock();
        $expectation = $endpoint->shouldReceive('iteratorForPrimary')->andReturnUsing(fn () => LazyCollection::make($transactions));
        if ($times !== null) {
            $expectation->times($times);
        }

        $api = Mockery::mock(MollieApiClient::class);
        $api->balanceTransactions = $endpoint;

        $this->app->instance('mollie.org_client', $api);
    }

    private function pdfText(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $m);

        return implode("\n", array_map(fn ($s) => @gzuncompress($s) ?: $s, $m[1]));
    }

    public function test_sync_zet_kosten_btw_en_netto_op_de_order(): void
    {
        $order = $this->order();
        $this->mockBalance([$this->transaction('tr_betaald1', '19.75', '-0.39')]);

        $this->artisan('mollie:sync-fees', ['--all' => true])
            ->expectsOutputToContain('PMP-261028-2627: € 0,39')
            ->expectsOutputToContain('1 betalingen/terugstortingen gevonden in Mollie, 1 bijgewerkt')
            ->assertSuccessful();

        $order->refresh();
        $this->assertSame(39, $order->mollie_fee);
        $this->assertSame(7, $order->mollie_fee_vat); // 39 − round(39 / 1,21) = 39 − 32
        $this->assertSame(1936, $order->mollie_net_received);
        $this->assertNotNull($order->mollie_balance_transaction_id);
        $this->assertNotNull($order->mollie_fee_synced_at);
    }

    public function test_sync_overschrijft_de_oude_nul_op_de_factuur(): void
    {
        $order = $this->order();
        $invoice = InvoiceService::createForOrder($order);
        // Zo stond het er tot nu toe: bedrag − settlementAmount = 0
        $invoice->update(['mollie_fee' => 0, 'net_received' => 1975]);

        $this->mockBalance([$this->transaction('tr_betaald1', '19.75', '-0.39')]);
        $this->artisan('mollie:sync-fees', ['--all' => true])->assertSuccessful();

        $invoice->refresh();
        $this->assertSame(39, $invoice->mollie_fee);
        $this->assertSame(1936, $invoice->net_received);
    }

    public function test_sync_is_idempotent(): void
    {
        $order = $this->order();
        $this->mockBalance([$this->transaction('tr_betaald1', '19.75', '-0.39')]);

        $this->artisan('mollie:sync-fees', ['--all' => true])->assertSuccessful();
        $syncedAt = $order->refresh()->mollie_fee_synced_at;

        $this->travel(1)->days();
        $this->artisan('mollie:sync-fees', ['--all' => true])
            ->expectsOutputToContain('1 betalingen/terugstortingen gevonden in Mollie, 0 bijgewerkt, 1 al goed')
            ->assertSuccessful();

        $order->refresh();
        $this->assertSame(39, $order->mollie_fee);
        $this->assertEquals($syncedAt, $order->mollie_fee_synced_at);
    }

    public function test_andere_transacties_en_onbekende_betalingen_worden_overgeslagen(): void
    {
        $order = $this->order();
        $other = $this->order(['order_number' => 'PMP-261028-9999', 'mollie_payment_id' => 'tr_betaald2']);

        $refund = $this->transaction('tr_betaald2', '-19.75', '-0.25');
        $refund->type = 'refund';

        $this->mockBalance([
            $this->transaction('tr_vanandereshop', '50.00', '-0.39'),
            $refund,
            $this->transaction('tr_betaald1', '19.75', '-0.39', nestedContext: true),
        ]);

        $this->artisan('mollie:sync-fees', ['--all' => true])->assertSuccessful();

        $this->assertSame(39, $order->refresh()->mollie_fee); // ook met context.payment.paymentId
        $this->assertNull($other->refresh()->mollie_fee);
    }

    public function test_betaling_zonder_inhouding_blijft_onbekend_in_plaats_van_nul(): void
    {
        $order = $this->order();
        $transaction = $this->transaction('tr_betaald1', '19.75', '0.00');
        $transaction->deductions = null; // Mollie laat het veld weg als er niets is ingehouden

        $this->mockBalance([$transaction]);

        $this->artisan('mollie:sync-fees', ['--all' => true])
            ->expectsOutputToContain('1 transactie(s) zonder ingehouden kosten')
            ->assertSuccessful();

        $this->assertNull($order->refresh()->mollie_fee);
    }

    public function test_days_stopt_bij_oudere_transacties(): void
    {
        $this->travelTo('2026-11-06 04:00:00');
        $recent = $this->order(['order_number' => 'PMP-261105-0001', 'mollie_payment_id' => 'tr_recent']);
        $old = $this->order(['order_number' => 'PMP-260801-0001', 'mollie_payment_id' => 'tr_oud']);

        $this->mockBalance([
            $this->transaction('tr_recent', '19.75', '-0.39', '2026-11-05T12:00:00+00:00'),
            $this->transaction('tr_oud', '19.75', '-0.39', '2026-08-01T12:00:00+00:00'),
        ]);

        $this->artisan('mollie:sync-fees', ['--days' => 14])
            ->expectsOutputToContain('1 betaalde order(s)/creditnota')
            ->assertSuccessful();

        $this->assertSame(39, $recent->refresh()->mollie_fee);
        $this->assertNull($old->refresh()->mollie_fee);
    }

    public function test_order_optie_werkt_alleen_die_order_bij(): void
    {
        $order = $this->order();
        $other = $this->order(['order_number' => 'PMP-261028-9999', 'mollie_payment_id' => 'tr_betaald2']);

        $this->mockBalance([
            $this->transaction('tr_betaald2', '19.75', '-0.39'),
            $this->transaction('tr_betaald1', '19.75', '-0.39', '2025-01-01T10:00:00+00:00'),
        ]);

        $this->artisan('mollie:sync-fees', ['--order' => 'PMP-261028-2627'])->assertSuccessful();

        $this->assertSame(39, $order->refresh()->mollie_fee);
        $this->assertNull($other->refresh()->mollie_fee);
    }

    public function test_zonder_organization_token_faalt_het_command_zonder_api_verkeer(): void
    {
        config(['mollie_fees.org_token' => null]);
        $this->mockBalance([], times: 0);

        $this->artisan('mollie:sync-fees', ['--all' => true])
            ->expectsOutputToContain('MOLLIE_ORG_TOKEN ontbreekt')
            ->assertFailed();
    }

    public function test_btw_bovenop_de_kosten_als_mollie_exclusief_inhoudt(): void
    {
        config(['mollie_fees.vat_included' => false]);
        $order = $this->order();
        $this->mockBalance([$this->transaction('tr_betaald1', '19.75', '-0.32')]);

        $this->artisan('mollie:sync-fees', ['--all' => true])->assertSuccessful();

        $this->assertSame(32, $order->refresh()->mollie_fee);
        $this->assertSame(7, $order->mollie_fee_vat); // round(32 × 0,21)
    }

    public function test_factuur_neemt_bekende_kosten_van_de_order_over(): void
    {
        $order = $this->order(['mollie_fee' => 39, 'mollie_fee_vat' => 7, 'mollie_net_received' => 1936]);

        $invoice = InvoiceService::createForOrder($order);

        $this->assertSame(39, $invoice->mollie_fee);
        $this->assertSame(1936, $invoice->net_received);
    }

    public function test_adminlijst_toont_de_mollie_kosten(): void
    {
        $this->order(['mollie_fee' => 39, 'mollie_fee_vat' => 7, 'mollie_net_received' => 1936]);
        $this->order(['order_number' => 'PMP-261028-9999', 'mollie_payment_id' => 'tr_betaald2']);

        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Mollie − € 0,39')
            ->assertSee('Mollie-kosten volgen');
    }

    public function test_maandoverzicht_toont_echte_kosten_btw_en_netto(): void
    {
        $this->order(['mollie_fee' => 39, 'mollie_fee_vat' => 7, 'mollie_net_received' => 1936]);
        $this->order([
            'order_number' => 'PMP-261029-0001', 'mollie_payment_id' => 'tr_betaald2', 'paid_at' => '2026-10-29 10:00:00',
            'mollie_fee' => 39, 'mollie_fee_vat' => 7, 'mollie_net_received' => 1936,
        ]);

        $text = $this->pdfText(MonthlyInvoiceSummaryService::generate(2026, 10));

        $this->assertStringContainsString("(- \x80 0,78) Tj", $text);   // transactiekosten, niet meer 0,00
        $this->assertStringContainsString('waarvan btw', $text);
        $this->assertStringContainsString("(\x80 0,14) Tj", $text);     // btw over de kosten
        $this->assertStringContainsString("(\x80 38,72) Tj", $text);    // netto = 39,50 − 0,78
        $this->assertStringNotContainsString('nog niet bekend', $text);
    }

    public function test_maandoverzicht_meldt_orders_zonder_bekende_kosten(): void
    {
        $this->order();

        $text = $this->pdfText(MonthlyInvoiceSummaryService::generate(2026, 10));

        $this->assertStringContainsString('zijn de Mollie-kosten nog niet bekend', $text);
    }

    /** Creditnota zoals CreditNoteService die voor een Mollie-terugbetaling maakt. */
    private function creditNote(Order $order, array $overrides = []): CreditNote
    {
        return CreditNote::create(array_merge([
            'order_id' => $order->id,
            'credit_number' => 'C-PMP-261028-0001',
            'mollie_refund_id' => 're_terug1',
            'credit_date' => '2026-10-28',
            'amount_excl_btw' => 1533,
            'amount_btw' => 322,
            'amount_incl_btw' => 1855,
            'btw_percentage' => 21,
        ], $overrides));
    }

    /** Refund-transactie zoals de Balances API die geeft (context: paymentId, refundId, ...). */
    private function refundTransaction(string $paymentId, string $refundId, string $amount, string $deduction): object
    {
        $transaction = $this->transaction($paymentId, $amount, $deduction, '2026-10-28T15:00:00+00:00');
        $transaction->type = 'refund';
        $transaction->id = 'baltr_'.substr(md5($refundId), 0, 10);
        $transaction->context->refundId = $refundId;

        return $transaction;
    }

    public function test_kosten_van_een_terugstorting_komen_op_de_creditnota(): void
    {
        $order = $this->order(['price_total' => 1855]);
        $creditNote = $this->creditNote($order);

        $this->mockBalance([
            $this->refundTransaction('tr_betaald1', 're_terug1', '-18.55', '-0.31'), // nieuwer dan de betaling
            $this->transaction('tr_betaald1', '18.55', '-0.39'),
        ]);

        $this->artisan('mollie:sync-fees', ['--all' => true])
            ->expectsOutputToContain('C-PMP-261028-0001: € 0,31')
            ->expectsOutputToContain('2 betalingen/terugstortingen gevonden in Mollie, 2 bijgewerkt')
            ->assertSuccessful();

        $creditNote->refresh();
        $this->assertSame(31, $creditNote->mollie_fee);
        $this->assertSame(5, $creditNote->mollie_fee_vat); // 31 − round(31 / 1,21) = 31 − 26
        $this->assertSame(39, $order->refresh()->mollie_fee);
        $this->assertSame(70, $order->total_mollie_fee);

        // Tweede run verandert niets
        $this->artisan('mollie:sync-fees', ['--all' => true])
            ->expectsOutputToContain('0 bijgewerkt, 2 al goed')
            ->assertSuccessful();
    }

    public function test_adminlijst_telt_terugstortingskosten_mee(): void
    {
        $order = $this->order(['mollie_fee' => 39, 'mollie_fee_vat' => 7, 'mollie_net_received' => 1936]);
        $this->creditNote($order, ['mollie_fee' => 31, 'mollie_fee_vat' => 5]);

        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Mollie − € 0,70')
            ->assertSee('terugstorting € 0,31');
    }

    public function test_maandoverzicht_telt_terugstortingskosten_in_de_maand_van_de_creditnota(): void
    {
        $order = $this->order(['mollie_fee' => 39, 'mollie_fee_vat' => 7, 'mollie_net_received' => 1936]);
        $this->creditNote($order, ['amount_excl_btw' => 1632, 'amount_btw' => 343, 'amount_incl_btw' => 1975, 'mollie_fee' => 31, 'mollie_fee_vat' => 5]);

        $october = $this->pdfText(MonthlyInvoiceSummaryService::generate(2026, 10));
        $this->assertStringContainsString("(- \x80 0,70) Tj", $october);  // 0,39 betaling + 0,31 terugstorting
        $this->assertStringContainsString("(\x80 0,12) Tj", $october);    // btw 0,07 + 0,05
        $this->assertStringContainsString('waarvan terugstortingen', $october);
        $this->assertStringContainsString("(\x80 -0,70) Tj", $october);   // netto: 19,75 − 19,75 − 0,70

        // Terugbetaling in november: de kosten verhuizen mee naar november
        CreditNote::query()->update(['credit_date' => '2026-11-02']);
        $october = $this->pdfText(MonthlyInvoiceSummaryService::generate(2026, 10));
        $november = $this->pdfText(MonthlyInvoiceSummaryService::generate(2026, 11));
        $this->assertStringContainsString("(- \x80 0,39) Tj", $october);
        $this->assertStringContainsString("(- \x80 0,31) Tj", $november);
    }

    public function test_sync_staat_dagelijks_in_de_planning(): void
    {
        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'mollie:sync-fees'));

        $this->assertCount(1, $events);
    }
}
