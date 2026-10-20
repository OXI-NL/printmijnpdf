<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Pdf\ExtendedPdfParser;
use App\Services\BookletImpositionService;
use App\Services\CutStackImpositionService;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\TestCase;

/**
 * Boekje-impositie in PHP (FPDI), zonder Python.
 */
class BookletImpositionTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_XREFSTREAM = __DIR__ . '/../Fixtures/a4-6-paginas-xrefstream.pdf';

    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->tempFiles, 'file_exists'));
        parent::tearDown();
    }

    /** Maakt een PDF met $pages pagina's van $width × $height mm. */
    private function maakPdf(int $pages, float $width = 210, float $height = 297): string
    {
        $pdf = new FPDF('P', 'mm', [$width, $height]);
        $pdf->SetFont('Helvetica', '', 40);
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage();
            $pdf->Text(20, 40, (string) $i);
        }

        $path = $this->tempFiles[] = tempnam(sys_get_temp_dir(), 'pmp');
        $pdf->Output('F', $path);

        return $path;
    }

    private function lees(string $pdf): PdfReader
    {
        return new PdfReader(new ExtendedPdfParser(StreamReader::createByString($pdf)));
    }

    public function test_a4_boekje_op_liggende_sra3_vellen(): void
    {
        $reader = $this->lees((new BookletImpositionService)->impose($this->maakPdf(10), 'A4'));

        $this->assertSame(6, $reader->getPageCount(), '10 pagina\'s → 3 vellen, voor en achter');
        [$width, $height] = $reader->getPage(1)->getWidthAndHeight();
        $this->assertEqualsWithDelta(1275.59, $width, 0.1);
        $this->assertEqualsWithDelta(907.09, $height, 0.1);
    }

    public function test_a5_boekje_op_sra4_vellen(): void
    {
        $reader = $this->lees((new BookletImpositionService)->impose($this->maakPdf(8, 148, 210), 'A5'));

        $this->assertSame(4, $reader->getPageCount());
        [$width, $height] = $reader->getPage(1)->getWidthAndHeight();
        $this->assertEqualsWithDelta(907.09, $width, 0.1);
        $this->assertEqualsWithDelta(637.80, $height, 0.1);
    }

    public function test_paginas_staan_op_ware_grootte_tegen_de_rug(): void
    {
        $reader = $this->lees((new BookletImpositionService)->impose($this->maakPdf(8), 'A4'));
        $content = $reader->getPage(1)->getContentStream();

        preg_match_all('/([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm \/TPL/', $content, $m, PREG_SET_ORDER);
        $this->assertCount(2, $m, 'twee pagina\'s op de voorkant van vel 1');

        $spine = 1275.59 / 2;
        [$left, $right] = $m;

        $this->assertEqualsWithDelta(1.0, (float) $left[1], 0.0001, 'geen verkleining');
        $this->assertEqualsWithDelta($spine - 595.28, (float) $left[3], 0.05, 'linkerpagina eindigt op de rug');
        $this->assertEqualsWithDelta($spine, (float) $right[3], 0.05, 'rechterpagina begint op de rug');
        $this->assertEqualsWithDelta((907.09 - 841.89) / 2, (float) $left[4], 0.05, 'verticaal gecentreerd');
    }

    public function test_afloop_zonder_trimbox_valt_buiten_de_rug(): void
    {
        $bleed = 3 * 72 / 25.4;
        $pdf = $this->maakPdf(4, 216, 303); // A4 + 3 mm afloop rondom
        $reader = $this->lees((new BookletImpositionService)->impose($pdf, 'A4', $bleed));

        preg_match_all('/([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm \/TPL/', $reader->getPage(1)->getContentStream(), $m, PREG_SET_ORDER);
        [$left, $right] = $m;
        $spine = 1275.59 / 2;

        // Snijkader (niet de afloop) sluit aan op de rug
        $this->assertEqualsWithDelta($spine - 595.28 - $bleed, (float) $left[3], 0.05);
        $this->assertEqualsWithDelta($spine - $bleed, (float) $right[3], 0.05);
        $this->assertStringContainsString('re W n', $reader->getPage(1)->getContentStream(), 'afloop aan de rugkant wordt afgeknipt');
    }

    public function test_snijtekens_op_3_mm_van_het_snijkader_en_7_mm_lang(): void
    {
        $reader = $this->lees((new BookletImpositionService)->impose($this->maakPdf(8), 'A4'));
        $content = $reader->getPage(1)->getContentStream();

        preg_match_all('/([\d.]+) ([\d.]+) m ([\d.]+) ([\d.]+) l S/', $content, $m, PREG_SET_ORDER);
        $this->assertCount(10, $m, '4 hoeken × 2 tekens + vouwlijn boven en onder de rug');

        $mm = 72 / 25.4;
        $spine = 1275.59 / 2;
        $trimLeft = $spine - 595.28;
        $trimTop = (907.09 + 841.89) / 2;

        // FPDF schrijft y van onder; de horizontale linksboven loopt van 3 mm tot 10 mm links van het snijkader
        $linksboven = array_values(array_filter($m, fn ($l) => abs((float) $l[2] - $trimTop) < 0.05 && (float) $l[1] < $trimLeft));
        $this->assertCount(1, $linksboven);
        $this->assertEqualsWithDelta($trimLeft - 3 * $mm, (float) $linksboven[0][1], 0.05);
        $this->assertEqualsWithDelta($trimLeft - 10 * $mm, (float) $linksboven[0][3], 0.05);

        $rug = array_filter($m, fn ($l) => abs((float) $l[1] - $spine) < 0.05 && abs((float) $l[3] - $spine) < 0.05);
        $this->assertCount(2, $rug, 'vouwlijn boven en onder de rug');
    }

    public function test_opdrachtregel_4_mm_onder_het_snijkader(): void
    {
        $reader = $this->lees((new BookletImpositionService)->impose($this->maakPdf(8), 'A4', 0.0, null, 'PMP-261019-684E'));

        $this->assertStringContainsString('(PMP-261019-684E | Geniete brochure | 4/4 | vel 1/2 voorkant) Tj', $reader->getPage(1)->getContentStream());
        $content = $reader->getPage(4)->getContentStream();
        $this->assertStringContainsString('(PMP-261019-684E | Geniete brochure | 4/4 | vel 2/2 achterkant) Tj', $content);

        // Bovenkant van de hoofdletters (basislijn + 0,718 × 6 pt) op 4 mm onder de onderkant van de pagina
        preg_match('/BT ([\d.]+) ([\d.]+) Td \(PMP/', $content, $m);
        $trimBottom = (907.09 - 841.89) / 2;
        $this->assertEqualsWithDelta($trimBottom - 4 * 72 / 25.4, (float) $m[2] + 0.718 * 6, 0.05);
    }

    public function test_losbladig_wordt_cut_and_stack(): void
    {
        $reader = $this->lees((new CutStackImpositionService)->impose($this->maakPdf(7), true, 0.0, null, 'PMP-LOS'));

        $this->assertSame(4, $reader->getPageCount(), '7 pagina\'s = 4 bladen, 2-op → 2 vellen, voor en achter');
        [$width, $height] = $reader->getPage(1)->getWidthAndHeight();
        $this->assertEqualsWithDelta(1275.59, $width, 0.1, 'liggend SRA3');
        $this->assertEqualsWithDelta(907.09, $height, 0.1);
        $this->assertStringContainsString('(PMP-LOS | Losbladig | 4/4 | vel 2/2 achterkant) Tj', $reader->getPage(4)->getContentStream());

        $enkel = $this->lees((new CutStackImpositionService)->impose($this->maakPdf(7), false, 0.0, null, 'PMP-LOS'));
        $this->assertSame(4, $enkel->getPageCount(), '7 pagina\'s 2-op → 4 vellen');
        $this->assertStringContainsString('(PMP-LOS | Losbladig | 4/0 | vel 1/4) Tj', $enkel->getPage(1)->getContentStream());
    }

    public function test_inslag_voor_losbladige_order(): void
    {
        Storage::disk('local')->put('pdfs/los.pdf', file_get_contents($this->maakPdf(5, 148, 210)));

        Order::create([
            'order_number' => 'PMP-LOSBLADIG', 'status' => 'paid',
            'pdf_original_name' => 'los.pdf', 'pdf_stored_name' => 'los.pdf', 'pdf_path' => 'pdfs/los.pdf',
            'page_count' => 5, 'format' => 'A5', 'binding_type' => 'loose', 'print_side' => 'single', 'quantity' => 1,
            'price_startup' => 0, 'price_pages' => 0, 'price_binding' => 0, 'price_shipping' => 0, 'price_total' => 0,
            'customer_name' => 'Test', 'customer_email' => 'test@example.com',
            'address_street' => 'Straat', 'address_number' => '1', 'address_postcode' => '1234AB', 'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders/PMP-LOSBLADIG/imposed')
            ->assertOk();

        $reader = $this->lees(Storage::disk('local')->get('pdfs/imposed/los_imposed.pdf'));
        $this->assertSame(2, $reader->getPageCount(), '5 pagina\'s A5 4-op → 2 vellen');
        $this->assertStringContainsString('(PMP-LOSBLADIG | Losbladig | 4/0 | vel 1/2) Tj', $reader->getPage(1)->getContentStream());
    }

    public function test_pdf_met_gecomprimeerde_xref_wordt_verwerkt(): void
    {
        // PDF 1.5 met xref stream, PNG-predictor en object streams: weigert de gratis FPDI-parser
        $reader = $this->lees((new BookletImpositionService)->impose(self::FIXTURE_XREFSTREAM, 'A4'));

        $this->assertSame(4, $reader->getPageCount(), '6 pagina\'s → 8 → 2 vellen');
    }

    public function test_gratis_fpdi_parser_weigert_de_fixture(): void
    {
        // Borgt dat de fixture echt het geval test waarvoor de eigen parser bestaat
        $this->expectExceptionMessage('compression technique which is not supported');

        (new \setasign\Fpdi\Fpdi())->setSourceFile(self::FIXTURE_XREFSTREAM);
    }

    public function test_impositie_voor_order_wordt_opgeslagen(): void
    {
        Storage::disk('local')->put('pdfs/boekje.pdf', file_get_contents($this->maakPdf(8)));

        $order = Order::create([
            'order_number' => 'PMP-IMPOSITIE', 'status' => 'paid',
            'pdf_original_name' => 'boekje.pdf', 'pdf_stored_name' => 'boekje.pdf', 'pdf_path' => 'pdfs/boekje.pdf',
            'page_count' => 8, 'format' => 'A4', 'binding_type' => 'booklet', 'print_side' => 'double', 'quantity' => 1,
            'price_startup' => 0, 'price_pages' => 0, 'price_binding' => 0, 'price_shipping' => 0, 'price_total' => 0,
            'customer_name' => 'Test', 'customer_email' => 'test@example.com',
            'address_street' => 'Straat', 'address_number' => '1', 'address_postcode' => '1234AB', 'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ]);

        $result = (new BookletImpositionService)->createImposition($order);

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('pdfs/imposed/boekje_imposed.pdf', $order->refresh()->pdf_imposed_path);
        Storage::disk('local')->assertExists('pdfs/imposed/boekje_imposed.pdf');
    }

    public function test_inslag_knop_maakt_altijd_een_nieuwe_inslag(): void
    {
        Storage::disk('local')->put('pdfs/boekje.pdf', file_get_contents($this->maakPdf(8)));
        Storage::disk('local')->put('pdfs/imposed/boekje_imposed.pdf', 'oude inslag zonder snijtekens');

        Order::create([
            'order_number' => 'PMP-OUDEINSLAG', 'status' => 'paid',
            'pdf_original_name' => 'boekje.pdf', 'pdf_stored_name' => 'boekje.pdf', 'pdf_path' => 'pdfs/boekje.pdf',
            'pdf_imposed_path' => 'pdfs/imposed/boekje_imposed.pdf',
            'page_count' => 8, 'format' => 'A4', 'binding_type' => 'booklet', 'print_side' => 'double', 'quantity' => 1,
            'price_startup' => 0, 'price_pages' => 0, 'price_binding' => 0, 'price_shipping' => 0, 'price_total' => 0,
            'customer_name' => 'Test', 'customer_email' => 'test@example.com',
            'address_street' => 'Straat', 'address_number' => '1', 'address_postcode' => '1234AB', 'address_city' => 'Delft',
            'delivery_type' => 'shipping',
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->get('/admin/orders/PMP-OUDEINSLAG/imposed')
            ->assertOk();

        $pdf = Storage::disk('local')->get('pdfs/imposed/boekje_imposed.pdf');
        $this->assertStringStartsWith('%PDF', $pdf, 'de opgeslagen oude inslag is vervangen');
    }

    public function test_onleesbare_pdf_geeft_nette_foutmelding(): void
    {
        Storage::disk('local')->put('pdfs/kapot.pdf', 'dit is geen pdf');

        $order = new Order([
            'order_number' => 'PMP-KAPOT', 'binding_type' => 'booklet', 'format' => 'A4',
            'pdf_path' => 'pdfs/kapot.pdf', 'pdf_stored_name' => 'kapot.pdf',
        ]);

        $result = (new BookletImpositionService)->createImposition($order);

        $this->assertFalse($result['success']);
        $this->assertStringStartsWith('PDF kon niet worden verwerkt', $result['message']);
    }

    public function test_vel_volgt_de_snijmaat(): void
    {
        $sheet = fn (string $format, ?float $w = null, ?float $h = null) =>
            BookletImpositionService::sheetFor(new Order(['format' => $format, 'trim_width_mm' => $w, 'trim_height_mm' => $h]));

        $this->assertSame('A5', $sheet('A5'), 'staand A5 op SRA4');
        $this->assertSame('A5', $sheet('A5', 140, 140), 'klein vierkant past op een halve SRA4');
        $this->assertSame('A4', $sheet('A5', 210, 148.5), 'liggend A5 is te breed voor een halve SRA4');
        $this->assertSame('A4', $sheet('A4'));
        $this->assertSame('A4', $sheet('A3', 222, 304));
    }
}
