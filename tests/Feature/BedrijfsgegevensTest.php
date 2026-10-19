<?php

namespace Tests\Feature;

use App\Mail\OrderShipped;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** KvK en BTW-id van NIVO staan op factuur, site en klantmail */
class BedrijfsgegevensTest extends TestCase
{
    use RefreshDatabase;

    private const KVK = '68331177';
    private const BTW = 'NL857396183B01';

    private function order(): Order
    {
        return Order::create([
            'order_number' => 'PMP-'.strtoupper(uniqid()),
            'status' => 'paid',
            'paid_at' => now(),
            'shipped_at' => now(),
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'stored.pdf',
            'pdf_path' => 'pdfs/stored.pdf',
            'page_count' => 8,
            'format' => 'A4',
            'binding_type' => 'loose',
            'print_side' => 'single',
            'quantity' => 1,
            'price_startup' => 1000,
            'price_pages' => 80,
            'price_binding' => 0,
            'price_shipping' => 750,
            'price_total' => 1830,
            'customer_name' => 'Jan Jansen',
            'customer_email' => 'jan@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ]);
    }

    public function test_factuur_krijgt_kvk_en_btw_id_van_nivo(): void
    {
        $invoice = InvoiceService::createForOrder($this->order());

        $this->assertSame(self::KVK, $invoice->kvk_number);
        $this->assertSame(self::BTW, $invoice->btw_id);
        $this->assertSame('Exportweg 11', $invoice->company_address);
    }

    public function test_site_en_mail_tonen_kvk_en_btw_id(): void
    {
        foreach (['/', route('over-ons'), route('privacy')] as $url) {
            $this->get($url)->assertOk()->assertSee(self::KVK)->assertSee(self::BTW);
        }

        $html = (new OrderShipped($this->order()))->render();
        $this->assertStringContainsString(self::KVK, $html);
        $this->assertStringContainsString(self::BTW, $html);
    }

    public function test_command_zet_echte_gegevens_op_oude_facturen(): void
    {
        Storage::fake('local');
        $invoice = InvoiceService::createForOrder($this->order());
        $invoice->update(['kvk_number' => '12345678', 'btw_id' => 'NL123456789B01', 'company_address' => 'Straatnaam 1', 'company_city' => 'Amsterdam']);
        Storage::disk('local')->put($invoice->pdf_path, 'oude pdf');

        $this->artisan('invoices:refresh-company-data', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('12345678', $invoice->fresh()->kvk_number);

        $this->artisan('invoices:refresh-company-data')
            ->expectsOutputToContain('1 factuur/facturen bijgewerkt')
            ->assertSuccessful();

        $invoice->refresh();
        $this->assertSame(self::KVK, $invoice->kvk_number);
        $this->assertSame(self::BTW, $invoice->btw_id);
        $this->assertSame('Delfgauw', $invoice->company_city);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($invoice->pdf_path));

        // Tweede keer: niets meer te doen
        $this->artisan('invoices:refresh-company-data')->expectsOutputToContain('0 factuur/facturen bijgewerkt');
    }
}
