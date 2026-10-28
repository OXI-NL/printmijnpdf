<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Archiveren bewaart de order maar verwijdert de PDF, inslag en thumbnail (privacyverklaring) */
class AdminArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $overrides = []): Order
    {
        $name = 'pdfs/'.uniqid().'.pdf';
        Storage::disk('local')->put($name, '%PDF-1.4');

        return Order::create(array_merge([
            'order_number' => 'PMP-'.strtoupper(uniqid()),
            'status' => 'shipped',
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => basename($name),
            'pdf_path' => $name,
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
            'customer_name' => 'Roxanne Planken',
            'customer_email' => 'roxanne@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ], $overrides));
    }

    private function admin(): static
    {
        return $this->withSession(['admin_authenticated' => true]);
    }

    public function test_archiveren_bewaart_order_en_verwijdert_bestanden(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $thumb = 'pdfs/'.pathinfo($order->pdf_path, PATHINFO_FILENAME).'-thumb.png';
        $imposed = 'imposed/'.$order->order_number.'.pdf';
        Storage::disk('local')->put($thumb, 'png');
        Storage::disk('local')->put($imposed, '%PDF-1.4');
        $order->update(['pdf_imposed_path' => $imposed]);
        $pdf = $order->pdf_path;

        $this->admin()
            ->post('/admin/orders/bulk-archive', ['order_numbers' => [$order->order_number]])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertNotNull($order->archived_at);
        $this->assertNull($order->pdf_path);
        $this->assertNull($order->pdf_imposed_path);
        $this->assertSame('Roxanne Planken', $order->customer_name);
        $this->assertSame(1830, $order->price_total);
        Storage::disk('local')->assertMissing($pdf);
        Storage::disk('local')->assertMissing($thumb);
        Storage::disk('local')->assertMissing($imposed);
    }

    public function test_gearchiveerde_order_staat_alleen_in_het_archief(): void
    {
        Storage::fake('local');
        $actief = $this->order(['customer_name' => 'Actieve Klant']);
        $oud = $this->order(['customer_name' => 'Gearchiveerde Klant', 'archived_at' => now()]);

        $this->admin()->get('/admin/orders')
            ->assertOk()
            ->assertSee($actief->order_number)
            ->assertDontSee($oud->order_number)
            ->assertSee('Archief (1)');

        $this->admin()->get('/admin/orders?archief=1')
            ->assertOk()
            ->assertSee($oud->order_number)
            ->assertDontSee($actief->order_number)
            ->assertSee('Terugzetten');
    }

    public function test_terugzetten_haalt_order_uit_archief(): void
    {
        Storage::fake('local');
        $order = $this->order(['archived_at' => now(), 'pdf_path' => null]);

        $this->admin()
            ->post('/admin/orders/bulk-unarchive', ['order_numbers' => [$order->order_number]])
            ->assertSessionHas('success');

        $this->assertNull($order->fresh()->archived_at);
    }

    public function test_archiveren_vereist_inlog(): void
    {
        Storage::fake('local');
        $order = $this->order();

        $this->post('/admin/orders/bulk-archive', ['order_numbers' => [$order->order_number]])
            ->assertRedirect();

        $this->assertNull($order->fresh()->archived_at);
    }

    public function test_opruimtaak_verwijdert_ook_thumbnail_en_inslag(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $order->forceFill(['created_at' => now()->subDays(10)])->save();
        $thumb = 'pdfs/'.pathinfo($order->pdf_path, PATHINFO_FILENAME).'-thumb.png';
        $imposed = 'imposed/'.$order->order_number.'.pdf';
        Storage::disk('local')->put($thumb, 'png');
        Storage::disk('local')->put($imposed, '%PDF-1.4');
        $order->update(['pdf_imposed_path' => $imposed]);

        $this->artisan('pdfs:cleanup', ['--days' => 7])->assertSuccessful();

        Storage::disk('local')->assertMissing($thumb);
        Storage::disk('local')->assertMissing($imposed);
        $this->assertNull($order->fresh()->pdf_imposed_path);
    }
}
