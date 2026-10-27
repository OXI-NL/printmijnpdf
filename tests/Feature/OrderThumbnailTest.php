<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Services\OrderThumbnailService;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * De browser stuurt een PNG van pagina 1 mee; de bevestigingsmail toont die
 * inline (CID). Zonder bruikbare PNG gaan bestelling en mail gewoon door.
 */
class OrderThumbnailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['mail.default' => 'array']);

        Http::fake(['api.mollie.com/*' => Http::response([
            'resource' => 'payment',
            'id' => 'tr_TestThumb01',
            'mode' => 'test',
            'status' => 'open',
            'amount' => ['value' => '10.00', 'currency' => 'EUR'],
            '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/test', 'type' => 'text/html']],
        ], 201)]);
    }

    private function png(int $w, int $h): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 20, 60, 110));
        ob_start();
        imagepng($img);

        return UploadedFile::fake()->createWithContent('thumbnail.png', ob_get_clean());
    }

    private function bestel(array $overrides = [])
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        return $this->postJson('/api/order', array_merge([
            'pdf' => UploadedFile::fake()->createWithContent('document.pdf', $pdf->Output('S')),
            'binding_type' => 'loose',
            'print_side' => 'double',
            'quantity' => 1,
            'delivery_type' => 'shipping',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'street' => 'Dorpsstraat',
            'number' => '12',
            'postcode' => '1234AB',
            'city' => 'Delft',
            'no_bleed_accepted' => '1',
        ], $overrides));
    }

    private function order(): Order
    {
        return Order::create([
            'order_number' => 'PMP-'.uniqid(),
            'status' => 'paid',
            'pdf_original_name' => 'document.pdf',
            'pdf_stored_name' => 'stored.pdf',
            'pdf_path' => 'pdfs/stored.pdf',
            'page_count' => 4,
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
    }

    private function sentEmail(Order $order): Email
    {
        Mail::to($order->customer_email)->send(new OrderConfirmation($order));

        return app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    }

    public function test_png_uit_de_browser_wordt_verkleind_naast_de_pdf_bewaard(): void
    {
        $this->bestel(['pdf_thumbnail' => $this->png(600, 848)])->assertOk();

        $path = OrderThumbnailService::existingPath(Order::sole());
        $this->assertNotNull($path);
        $this->assertStringEndsWith('-thumb.png', $path);

        [$w, $h, $type] = getimagesizefromstring(Storage::disk('local')->get($path));
        $this->assertSame(IMAGETYPE_PNG, $type);
        $this->assertSame(OrderThumbnailService::MAX_WIDTH, $w);
        $this->assertSame(339, $h);
    }

    public function test_bestelling_zonder_png_slaagt_zonder_thumbnail(): void
    {
        $this->bestel()->assertOk();

        $this->assertNull(OrderThumbnailService::existingPath(Order::sole()));
    }

    public function test_ongeldige_png_breekt_de_bestelling_niet(): void
    {
        $this->bestel([
            'pdf_thumbnail' => UploadedFile::fake()->createWithContent('thumbnail.png', '<?php echo 1;'),
        ])->assertOk();

        $this->assertNull(OrderThumbnailService::existingPath(Order::sole()));
    }

    public function test_png_met_enorme_pixelmaten_wordt_genegeerd(): void
    {
        $this->bestel(['pdf_thumbnail' => $this->png(2500, 10)])->assertOk();

        $this->assertNull(OrderThumbnailService::existingPath(Order::sole()));
    }

    public function test_bevestigingsmail_embedt_de_thumbnail_inline(): void
    {
        $order = $this->order();
        OrderThumbnailService::store($order, $this->png(240, 339));

        $email = $this->sentEmail($order);

        $this->assertMatchesRegularExpression('#<img src="cid:[^"]+" alt="Pagina 1 van je document"#', $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());
        $part = $email->getAttachments()[0];
        $this->assertSame('inline', $part->getDisposition());
        $this->assertSame('image/png', $part->getMediaType().'/'.$part->getMediaSubtype());
    }

    public function test_bevestigingsmail_zonder_thumbnail_wordt_gewoon_verstuurd(): void
    {
        $email = $this->sentEmail($this->order());

        $this->assertStringContainsString('Bestelling bevestigd', $email->getSubject());
        $this->assertStringNotContainsString('Pagina 1 van je document', $email->getHtmlBody());
        $this->assertCount(0, $email->getAttachments());
    }

    public function test_mail_renderen_zonder_bericht_slaat_de_thumbnail_over(): void
    {
        $order = $this->order();
        OrderThumbnailService::store($order, $this->png(240, 339));

        $html = (new OrderConfirmation($order))->render();

        $this->assertStringContainsString($order->order_number, $html);
    }
}
