<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\CreditNoteService;
use App\Services\MonthlyInvoiceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Terugbetalingen tellen netto mee in het maandoverzicht en de admin */
class CreditNoteAdminTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->order = Order::create([
            'order_number' => 'PMP-261028-2627',
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
        ]);

        $payment = new class {
            public function hasRefunds(): bool { return true; }
            public function refunds(): array
            {
                return [(object) [
                    'id' => 're_1', 'status' => 'refunded', 'description' => null,
                    'amount' => (object) ['value' => '19.75'], 'createdAt' => '2026-10-28T15:00:00+00:00',
                ]];
            }
        };
        CreditNoteService::syncRefunds($this->order, $payment);
    }

    private function pdfText(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $m);

        return implode("\n", array_map(fn ($s) => @gzuncompress($s) ?: $s, $m[1]));
    }

    public function test_maandoverzicht_trekt_creditnota_af(): void
    {
        $text = $this->pdfText(MonthlyInvoiceSummaryService::generate(2026, 10));

        $this->assertStringContainsString('C-PMP-261028-0001', $text);
        $this->assertStringContainsString('TERUGBETALINGEN', $text);
        // Bestelling 19,75 incl. min creditnota 19,75 = 0,00 netto
        $this->assertStringContainsString("(\x80 0,00) Tj", $text);
        $this->assertStringContainsString("(\x80 -19,75) Tj", $text);
    }

    public function test_creditnota_pdf_te_downloaden(): void
    {
        $response = $this->withSession(['admin_authenticated' => true])
            ->get('/admin/credit-notes/C-PMP-261028-0001')
            ->assertOk()
            ->assertDownload('Creditnota_C-PMP-261028-0001.pdf');

        $text = $this->pdfText($response->streamedContent());
        $this->assertStringContainsString('CREDITNOTA', $text);
        $this->assertStringContainsString('PMP-261028-2627', $text);
    }

    public function test_adminlijst_toont_terugbetaald_en_netto_omzet(): void
    {
        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Terugbetaald € 19,75')
            ->assertSee('/admin/credit-notes/C-PMP-261028-0001')
            ->assertSee('data-ship="PMP-261028-2627"', false) // verzenden blijft mogelijk
            ->assertSeeInOrder(['Omzet totaal', '€ 0,00']);
    }
}
