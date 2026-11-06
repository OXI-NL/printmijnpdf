<?php

namespace Tests\Feature;

use App\Console\Commands\ImportGa4Purchases;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ontvangst in GA4 is alleen vast te stellen uit een GA4-export. Fictieve
 * bestelnummers; geen verbinding met Google.
 */
class ImportGa4PurchasesTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $number, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => $number,
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

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ga4');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_nederlandse_export_met_kop_en_aantallen(): void
    {
        $counts = ImportGa4Purchases::parse(
            "\xEF\xBB\xBF# ----------------------------------------\n"
            ."# E-commerce-aankopen\n"
            ."# Begindatum: 20261103\n"
            ."Transactie-ID,E-commerce-aankopen,Opbrengst uit aankopen\n"
            ."PMP-261103-0001,1,19.75\n"
            ."PMP-261103-0002,2,\"1.234,50\"\n"
            ."(not set),3,0\n"
        );

        $this->assertSame(['PMP-261103-0001' => 1, 'PMP-261103-0002' => 2], $counts);
    }

    public function test_engelse_tsv_export_zonder_aantalkolom_telt_regels(): void
    {
        $counts = ImportGa4Purchases::parse("Transaction ID\tPurchase revenue\nPMP-1\t19.75\nPMP-1\t19.75\nPMP-2\t5.00\n");
        // 'Purchase revenue' is geen aantal; zonder aantalkolom telt elke regel één keer
        $this->assertSame(['PMP-1' => 2, 'PMP-2' => 1], $counts);
    }

    public function test_engelse_export_met_aantalkolom(): void
    {
        $counts = ImportGa4Purchases::parse("Transaction ID,Ecommerce purchases\nPMP-1,1\nGrand total,1\n");
        $this->assertSame(['PMP-1' => 1], $counts);
    }

    public function test_export_zonder_transactie_id_wordt_geweigerd(): void
    {
        $this->assertNull(ImportGa4Purchases::parse("Paginatitel,Weergaven\nHome,10\n"));

        $this->artisan('analytics:import-ga4', ['file' => $this->csv("Paginatitel,Weergaven\n"), '--from' => '2026-11-03', '--to' => '2026-11-03'])
            ->assertFailed();
    }

    public function test_import_legt_gevonden_dubbel_en_ontbrekend_vast(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $ok = $this->order('PMP-261103-0001');
        $double = $this->order('PMP-261103-0002');
        $missing = $this->order('PMP-261103-0003');
        $test = $this->order('PMP-261103-0004', ['payment_mode' => 'test']);
        $other = $this->order('PMP-261001-0009');
        $other->forceFill(['created_at' => now()->subMonth()])->save();

        $file = $this->csv("Transactie-ID,E-commerce-aankopen\nPMP-261103-0001,1\nPMP-261103-0002,2\nPMP-261001-0009,1\nPMP-999999-0000,1\n");

        $this->artisan('analytics:import-ga4', ['file' => $file, '--from' => '2026-11-03', '--to' => '2026-11-03'])
            ->expectsTable(['Uitkomst', 'Orders'], [
                ['Precies één keer in GA4', 1],
                ['Meer dan één keer in GA4 (dubbel)', 1],
                ['Ontbreekt in GA4', 1],
            ])
            ->expectsOutputToContain('PMP-261001-0009 (andere periode/status of test)')
            ->expectsOutputToContain('PMP-999999-0000 (onbekend)')
            ->assertSuccessful();

        $this->assertSame(1, $ok->refresh()->analytics_purchase_ga4_count);
        $this->assertSame(2, $double->refresh()->analytics_purchase_ga4_count);
        $this->assertSame(0, $missing->refresh()->analytics_purchase_ga4_count);
        $this->assertNull($test->refresh()->analytics_purchase_ga4_count, 'testbetaling niet meegewogen');
        $this->assertNull($other->refresh()->analytics_purchase_ga4_count, 'buiten de periode niet aangeraakt');

        $this->artisan('orders:report', ['--from' => '2026-11-03', '--to' => '2026-11-03', '--missing' => true])
            ->expectsOutputToContain('GA4-export: ontbreekt')
            ->expectsOutputToContain('| ontbreekt')
            ->doesntExpectOutputToContain('PMP-261103-0001')
            ->assertSuccessful();
    }

    public function test_proefdraai_slaat_niets_op(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $order = $this->order('PMP-261103-0001');

        $this->artisan('analytics:import-ga4', [
            'file' => $this->csv("Transactie-ID\nPMP-261103-0001\n"), '--from' => '2026-11-03', '--to' => '2026-11-03', '--dry-run' => true,
        ])->expectsOutputToContain('[proef]')->assertSuccessful();

        $this->assertNull($order->refresh()->analytics_purchase_ga4_count);
    }
}
