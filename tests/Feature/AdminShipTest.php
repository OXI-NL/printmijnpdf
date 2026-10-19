<?php

namespace Tests\Feature;

use App\Mail\OrderReadyForPickup;
use App\Mail\OrderShipped;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Verzenden en "Klaar voor afhalen" in de admin sturen de klant een mail */
class AdminShipTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $delivery): Order
    {
        return Order::create([
            'order_number' => 'PMP-'.strtoupper(uniqid()),
            'status' => 'paid',
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
            'price_shipping' => $delivery === 'shipping' ? 750 : 0,
            'price_total' => 1830,
            'customer_name' => "Roxanne D'Planken",
            'customer_email' => 'roxanne@example.com',
            'address_street' => 'Dorpsstraat',
            'address_number' => '12',
            'address_postcode' => '1234AB',
            'address_city' => 'Delft',
            'delivery_type' => $delivery,
        ]);
    }

    public function test_klaar_voor_afhalen_stuurt_afhaalmail(): void
    {
        Mail::fake();
        $order = $this->order('pickup');

        $this->withSession(['admin_authenticated' => true])
            ->post("/admin/orders/{$order->order_number}/ship", ['status' => 'shipped', 'pickup' => 1])
            ->assertSessionHas('success', "{$order->order_number}: Afhaalmail verstuurd naar roxanne@example.com.");

        Mail::assertSent(OrderReadyForPickup::class, fn ($m) => $m->hasTo('roxanne@example.com') && $m->hasCc('elco@nivo.com'));
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_verzenden_stuurt_verzendmail_met_track_trace(): void
    {
        Mail::fake();
        $order = $this->order('shipping');

        $this->withSession(['admin_authenticated' => true])
            ->post("/admin/orders/{$order->order_number}/ship", ['status' => 'shipped', 'track_trace' => '3SABC123'])
            ->assertSessionHas('success', "{$order->order_number}: Verzendmail verstuurd naar roxanne@example.com.");

        Mail::assertSent(OrderShipped::class);
        $this->assertSame('3SABC123', $order->fresh()->track_trace);
    }

    /** Een formulier binnen een ander formulier wordt door de browser genegeerd */
    public function test_adminpagina_heeft_geen_geneste_formulieren(): void
    {
        $this->order('pickup');
        $this->order('shipping');

        $html = $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders')->assertOk()->getContent();

        $depth = 0;
        preg_match_all('#<(/?)form\b#i', $html, $tags, PREG_SET_ORDER);
        foreach ($tags as $tag) {
            $depth += $tag[1] === '/' ? -1 : 1;
            $this->assertLessThanOrEqual(1, $depth, 'Genest <form> gevonden');
        }
        $this->assertSame(0, $depth);
        $this->assertStringContainsString('form="bulkDeleteForm"', $html);
    }

    /** "boekje@else" zonder spatie werd door Blade niet als directive gezien */
    public function test_mails_tonen_afwerking_zonder_blade_resten(): void
    {
        foreach (['booklet' => 'Geniet boekje', 'loose' => 'Losbladig (enkelzijdig)'] as $binding => $label) {
            $order = $this->order('pickup');
            $order->binding_type = $binding;
            $order->shipped_at = now();

            foreach ([new OrderReadyForPickup($order), new OrderShipped($order)] as $mail) {
                $html = $mail->render();
                $this->assertStringContainsString($label, $html);
                $this->assertStringNotContainsString('@else', $html);
                $this->assertStringContainsString('PMP_beeldmerk_wit.png', $html, 'Rode header vraagt om het witte beeldmerk');
            }
        }
    }
}
