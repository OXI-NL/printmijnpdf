<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Label maken via de PostNL API vanuit het verzendvenster in de admin */
class PostNLLabelTest extends TestCase
{
    use RefreshDatabase;

    private const LABEL_URL = 'https://api-sandbox.postnl.nl/shipment/v2_2/label*';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['postnl.api_key' => 'test-sleutel', 'postnl.sandbox' => true]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'PMP-'.strtoupper(uniqid()),
            'status' => 'paid',
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'stored.pdf',
            'pdf_path' => 'pdfs/stored.pdf',
            'page_count' => 8,
            'format' => 'A4',
            'binding_type' => 'booklet',
            'print_side' => 'double',
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
            'address_addition' => 'B',
            'address_postcode' => '1234 ab',
            'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ], $overrides));
    }

    private function fakePostNL(string $barcode = '3SHCBM123456789'): void
    {
        Http::fake([self::LABEL_URL => Http::response([
            'ResponseShipments' => [[
                'Barcode' => $barcode,
                'Labels' => [['Labeltype' => 'Label', 'OutputType' => 'PDF', 'Content' => base64_encode("%PDF-1.4 testlabel")]],
            ]],
        ])]);
    }

    private function admin()
    {
        return $this->withSession(['admin_authenticated' => true]);
    }

    public function test_label_maken_bewaart_pdf_en_barcode_zonder_klant_te_mailen(): void
    {
        $this->fakePostNL();
        $order = $this->order();

        $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'brievenbuspakje'])
            ->assertOk()
            ->assertJson(['barcode' => '3SHCBM123456789']);

        $order->refresh();
        $this->assertSame('3SHCBM123456789', $order->postnl_barcode);
        $this->assertSame('brievenbuspakje', $order->postnl_product);
        $this->assertSame('paid', $order->status, 'Pas na bevestigen gaat de order op verzonden');
        $this->assertNull($order->track_trace);
        Storage::disk('local')->assertExists($order->postnl_label_path);

        Http::assertSent(function (Request $request) use ($order) {
            $shipment = $request['Shipments'][0];

            return $request->hasHeader('apikey', 'test-sleutel')
                && str_contains($request->url(), 'confirm=true')
                && $request['Customer']['CustomerCode'] === 'HCBM'
                && $request['Customer']['CustomerNumber'] === '9121183'
                && $request['Message']['Printertype'] === 'GraphicFile|PDF'
                && $shipment['ProductCodeDelivery'] === '2948'
                && $shipment['Reference'] === $order->order_number
                && $shipment['Addresses'][0]['Zipcode'] === '1234AB'
                && $shipment['Addresses'][0]['HouseNrExt'] === 'B';
        });

        $this->admin()->get("/admin/orders/{$order->order_number}/label")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_bevestigen_zonder_ingevulde_code_gebruikt_labelcode(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->fakePostNL();
        $order = $this->order();
        $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'brievenbuspakje'])->assertOk();

        $this->admin()->post("/admin/orders/{$order->order_number}/ship", ['status' => 'shipped', 'track_trace' => ''])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('3SHCBM123456789', $order->track_trace);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\OrderShipped::class);
    }

    public function test_productcodes_per_verzendvorm(): void
    {
        foreach (['pakket' => '3085', 'brievenbuspakje_24' => '2928'] as $product => $code) {
            $this->fakePostNL();
            $order = $this->order();

            $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => $product])->assertOk();

            Http::assertSent(fn (Request $request) => $request['Shipments'][0]['ProductCodeDelivery'] === $code);
        }
    }

    public function test_tweede_label_alleen_na_bevestiging(): void
    {
        $this->fakePostNL();
        $order = $this->order();
        $url = "/admin/orders/{$order->order_number}/label";

        $this->admin()->postJson($url, ['product' => 'brievenbuspakje'])->assertOk();
        $this->admin()->postJson($url, ['product' => 'brievenbuspakje'])
            ->assertStatus(409)
            ->assertJson(['has_label' => true]);
        Http::assertSentCount(1);

        $this->admin()->postJson($url, ['product' => 'pakket', 'replace' => true])->assertOk();
        Http::assertSentCount(2);
        $this->assertSame('pakket', $order->fresh()->postnl_product);
    }

    public function test_foutmelding_van_postnl_komt_bij_de_admin(): void
    {
        Http::fake([self::LABEL_URL => Http::response([
            'Errors' => [['Code' => '13', 'Description' => 'Invalid zipcode']],
        ], 400)]);
        $order = $this->order();

        $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'brievenbuspakje'])
            ->assertStatus(502)
            ->assertJsonFragment(['error' => 'PostNL weigerde het label (HTTP 400): Invalid zipcode']);

        $this->assertNull($order->fresh()->postnl_label_path);
    }

    public function test_ongeldige_sleutel_geeft_duidelijke_melding(): void
    {
        Http::fake([self::LABEL_URL => Http::response(['message' => 'Unauthorized', 'http_status_code' => 401], 401)]);
        $order = $this->order();

        $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'pakket'])
            ->assertStatus(502)
            ->assertJsonFragment(['error' => 'PostNL accepteert de API-sleutel niet. Controleer POSTNL_API_KEY en POSTNL_SANDBOX.']);
    }

    public function test_buitenland_en_afhalen_blijven_handmatig(): void
    {
        Http::fake();

        foreach ([['address_country' => 'BE'], ['delivery_type' => 'pickup']] as $overrides) {
            $order = $this->order($overrides);
            $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'pakket'])
                ->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    public function test_zonder_api_sleutel_geen_label(): void
    {
        config(['postnl.api_key' => null]);
        Http::fake();
        $order = $this->order();

        $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'pakket'])
            ->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_label_vereist_admin(): void
    {
        $order = $this->order();

        $this->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'pakket'])->assertStatus(302);
        $this->get("/admin/orders/{$order->order_number}/label")->assertRedirect();
    }

    public function test_a3_standaard_als_pakket_in_verzendknop(): void
    {
        $this->order(['format' => 'A3']);
        $this->order();

        $html = $this->admin()->get('/admin/orders')->assertOk()->getContent();

        // Js::from schrijft aanhalingstekens als "
        $product = fn (string $p) => str_replace('"', '\\u0022', '"product":"'.$p.'"');

        $this->assertStringContainsString('Label maken via PostNL', $html);
        $this->assertStringContainsString('SANDBOX', $html);
        $this->assertSame(1, substr_count($html, $product('pakket')));
        $this->assertSame(1, substr_count($html, $product('brievenbuspakje')));
    }

    public function test_archiveren_verwijdert_het_label(): void
    {
        $this->fakePostNL();
        $order = $this->order(['status' => 'shipped']);
        $this->admin()->postJson("/admin/orders/{$order->order_number}/label", ['product' => 'pakket'])->assertOk();
        $path = $order->fresh()->postnl_label_path;

        $this->admin()->post('/admin/orders/bulk-archive', ['order_numbers' => [$order->order_number]]);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($order->fresh()->postnl_label_path);
    }
}
