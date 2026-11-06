<?php

namespace Tests\Feature;

use App\Console\Commands\ImportGa4Purchases;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
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

    private function rejects(string $csv, string $message): void
    {
        try {
            ImportGa4Purchases::parse($csv);
            $this->fail('Export had geweigerd moeten worden');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
    }

    public function test_nederlandse_export_met_kop_en_aantallen(): void
    {
        $counts = ImportGa4Purchases::parse(
            "\xEF\xBB\xBF# ----------------------------------------\n"
            ."# E-commerce-aankopen\n"
            ."# Begindatum: 20261103\n"
            ."Transactie-ID,E-commerce-aankopen,Opbrengst uit aankopen\n"
            ."Totaal,3,\"1.254,25\"\n"
            ."PMP-261103-0001,1,19.75\n"
            ."PMP-261103-0002,2,\"1.234,50\"\n"
            ."(not set),3,0\n"
        );

        $this->assertSame(['PMP-261103-0001' => 1, 'PMP-261103-0002' => 2], $counts);
    }

    public function test_csv_a_zonder_aankooptelling_wordt_geweigerd(): void
    {
        // Eén regel per transactie-ID zegt niets over hoe vaak de aankoop in GA4 staat
        $this->rejects("Transaction ID,Purchase revenue\nPMP-261031-1322,43.22\n", 'E-commerce-aankopen');
        $this->rejects("Transactie-ID\nPMP-261031-1322\n", 'E-commerce-aankopen');
    }

    public function test_csv_b_met_aankooptelling_toont_de_dubbele_aankoop(): void
    {
        $counts = ImportGa4Purchases::parse("Transaction ID,Ecommerce purchases,Purchase revenue\nPMP-261031-1322,2,43.22\n");
        $this->assertSame(['PMP-261031-1322' => 2], $counts);
    }

    public function test_algemene_tellingen_gelden_niet_als_aankooptelling(): void
    {
        $this->rejects("Transaction ID,Event count\nPMP-261031-1322,3\n", 'Aantal gebeurtenissen');
        $this->rejects("Transactie-ID,Aantal gebeurtenissen\nPMP-261031-1322,3\n", 'Aantal gebeurtenissen');
        $this->rejects("Transaction ID,Transactions\nPMP-261031-1322,1\n", 'Transacties');
    }

    public function test_ongeldig_aantal_wordt_niet_omgezet_in_een_ander_getal(): void
    {
        foreach (['1.5', '', 'twee', '-1', '1,234'] as $value) {
            $this->rejects("Transaction ID,Ecommerce purchases\nPMP-1,1\nPMP-2,\"{$value}\"\n", 'Regel 3 (PMP-2)');
        }
    }

    public function test_tsv_export_en_herhaalde_kopregel(): void
    {
        $counts = ImportGa4Purchases::parse(
            "Transaction ID\tEcommerce purchases\nPMP-1\t1\n"
            ."#\n# tweede tabel\nTransaction ID\tEcommerce purchases\nPMP-2\t1\nPMP-1\t1\n"
        );
        // Zelfde ID in twee tabellen/regels: opgeteld, dus zichtbaar als dubbel
        $this->assertSame(['PMP-1' => 2, 'PMP-2' => 1], $counts);
    }

    public function test_export_zonder_transactie_id_wordt_geweigerd(): void
    {
        $this->rejects("Paginatitel,Weergaven\nHome,10\n", 'Transactie-ID');

        $this->artisan('analytics:import-ga4', ['file' => $this->csv("Paginatitel,Weergaven\n"), '--from' => '2026-11-03', '--to' => '2026-11-03'])
            ->assertFailed();
    }

    public function test_geweigerde_export_wijzigt_niets(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(12, 0));
        $order = $this->order('PMP-261103-0001');

        $this->artisan('analytics:import-ga4', [
            'file' => $this->csv("Transaction ID,Purchase revenue\nPMP-261103-0001,19.75\n"), '--from' => '2026-11-03', '--to' => '2026-11-03',
        ])->expectsOutputToContain('E-commerce-aankopen')->assertFailed();

        $order->refresh();
        $this->assertNull($order->analytics_purchase_ga4_count);
        $this->assertNull($order->analytics_purchase_ga4_checked_at);
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
            'file' => $this->csv("Transactie-ID,E-commerce-aankopen\nPMP-261103-0001,1\n"), '--from' => '2026-11-03', '--to' => '2026-11-03', '--dry-run' => true,
        ])->expectsOutputToContain('[proef]')->assertSuccessful();

        $this->assertNull($order->refresh()->analytics_purchase_ga4_count);
    }
}
