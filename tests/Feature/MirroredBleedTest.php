<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Pdf\ExtendedPdfParser;
use App\Services\BookletImpositionService;
use App\Services\MirroredBleedService;
use App\Services\PdfFormatValidator;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\TestCase;

/**
 * Afloop aanvullen voor een PDF zonder afloop: de pagina blijft op 100% en
 * krijgt rondom 3 mm gespiegelde rand.
 */
class MirroredBleedTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_XREFSTREAM = __DIR__ . '/../Fixtures/a4-6-paginas-xrefstream.pdf';

    private const BLEED = 3 * 72 / 25.4;

    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->tempFiles, 'file_exists'));
        parent::tearDown();
    }

    private function temp(): string
    {
        return $this->tempFiles[] = tempnam(sys_get_temp_dir(), 'pmp');
    }

    /** PDF met $pages pagina's van $width × $height mm, met een kleurvlak tot de rand. */
    private function maakPdf(int $pages = 2, float $width = 210, float $height = 297, bool $link = false): string
    {
        $pdf = new FPDF('P', 'mm', [$width, $height]);
        $pdf->SetFont('Helvetica', '', 40);
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage($width > $height ? 'L' : 'P');
            $pdf->SetFillColor(230, 60, 40);
            $pdf->Rect(0, 0, 50, 50, 'F');
            $pdf->Text(20, 80, (string) $i);
            if ($link) {
                $pdf->Link(20, 60, 50, 20, 'https://printmijnpdf.nl');
            }
        }

        $path = $this->temp();
        $pdf->Output('F', $path);

        return $path;
    }

    /** Minimale PDF met een correcte xref-tabel; $pageExtra komt in het pagina-object. */
    private function handgemaakt(string $pageExtra = '', string $annot = ''): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents 4 0 R ' . $pageExtra . ' >>',
            "<< /Length 23 >>\nstream\n0 0 1 rg 0 0 50 50 re f\nendstream",
        ];
        if ($annot !== '') {
            $objects[] = $annot;
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $path = $this->temp();
        file_put_contents($path, $pdf);

        return $path;
    }

    private function metAfloop(string $input): string
    {
        $output = $this->temp();
        (new MirroredBleedService)->addBleed($input, $output);

        return $output;
    }

    private function lees(string $path): PdfReader
    {
        return new PdfReader(new ExtendedPdfParser(StreamReader::createByFile($path)));
    }

    /** @return float[] [llx, lly, urx, ury] */
    private function box(PdfReader $reader, int $page, string $name): array
    {
        $box = PdfType::resolve($reader->getPage($page)->getAttribute($name), $reader->getParser());
        $this->assertInstanceOf(PdfArray::class, $box, "$name ontbreekt");

        return array_map(fn ($v) => (float) $v->value, $box->value);
    }

    public function test_a4_krijgt_rondom_3_mm_en_een_trimbox_op_a4(): void
    {
        $reader = $this->lees($this->metAfloop($this->maakPdf(3)));

        $this->assertSame(3, $reader->getPageCount());
        for ($p = 1; $p <= 3; $p++) {
            $media = $this->box($reader, $p, 'MediaBox');
            $this->assertEqualsWithDelta(595.28 + 2 * self::BLEED, $media[2], 0.02);
            $this->assertEqualsWithDelta(841.89 + 2 * self::BLEED, $media[3], 0.02);

            $trim = $this->box($reader, $p, 'TrimBox');
            $this->assertEqualsWithDelta([self::BLEED, self::BLEED, self::BLEED + 595.28, self::BLEED + 841.89], $trim, 0.02);

            $this->assertEqualsWithDelta($media, $this->box($reader, $p, 'BleedBox'), 0.02);
        }
    }

    public function test_formaatcontrole_ziet_daarna_a4_met_3_mm_afloop(): void
    {
        $result = (new PdfFormatValidator)->validate($this->metAfloop($this->maakPdf()));

        $this->assertTrue($result['valid']);
        $this->assertSame('A4', $result['format']);
        $this->assertTrue($result['standard']);
        $this->assertTrue($result['has_bleed']);
        $this->assertEqualsWithDelta(3.0, $result['bleed_mm'], 0.05);
    }

    public function test_pagina_wordt_een_keer_recht_en_acht_keer_gespiegeld_geplaatst(): void
    {
        $reader = $this->lees($this->metAfloop($this->maakPdf(1)));
        $content = $reader->getPage(1)->getContentStream();

        $this->assertSame(9, preg_match_all('/\/TPL\d+ Do/', $content), 'midden, vier randen, vier hoeken');
        $this->assertSame(9, preg_match_all('/ re W n/', $content), 'elk vak is afgeknipt');

        // Spiegelen in de snijlijn: x' = 2·rand − x, per vak (a, d, e, f) van de matrix
        preg_match_all('/(-?1\.0+) 0\.0+ 0\.0+ (-?1\.0+) (-?[\d.]+) (-?[\d.]+) cm/', $content, $m, PREG_SET_ORDER);
        $actual = array_map(fn ($x) => array_map('floatval', array_slice($x, 1)), $m);
        [$b, $w, $h] = [self::BLEED, 595.28, 841.89];
        $expected = [];
        foreach ([[-1, 2 * $b], [1, 0], [-1, 2 * ($b + $w)]] as [$a, $e]) {
            foreach ([[-1, 2 * $b], [1, 0], [-1, 2 * ($b + $h)]] as [$d, $f]) {
                $expected[] = [(float) $a, (float) $d, $e, $f];
            }
        }
        $this->assertEqualsWithDelta($expected, $actual, 0.01);

        // Het middenvak is precies de pagina, dus de inhoud blijft op 100%
        $this->assertMatchesRegularExpression(
            sprintf('/q %.2F %.2F %.2F %.2F re W n/', self::BLEED, self::BLEED, 595.28, 841.89),
            $content
        );
    }

    public function test_liggend_en_eigen_formaat(): void
    {
        $reader = $this->lees($this->metAfloop($this->maakPdf(1, 297, 210)));
        $trim = $this->box($reader, 1, 'TrimBox');
        $this->assertEqualsWithDelta(841.89, $trim[2] - $trim[0], 0.02, 'liggend blijft liggend');
        $this->assertEqualsWithDelta(595.28, $trim[3] - $trim[1], 0.02);

        $result = (new PdfFormatValidator)->validate($this->metAfloop($this->maakPdf(1, 200, 200)));
        $this->assertSame([200.0, 200.0, true], [$result['detected_width_mm'], $result['detected_height_mm'], $result['has_bleed']]);
    }

    public function test_gedraaide_pagina_komt_rechtop(): void
    {
        $reader = $this->lees($this->metAfloop($this->handgemaakt('/Rotate 90')));
        $trim = $this->box($reader, 1, 'TrimBox');

        $this->assertEqualsWithDelta(841.89, $trim[2] - $trim[0], 0.02, 'zoals hij in een viewer staat: liggend');
        $this->assertEqualsWithDelta(595.28, $trim[3] - $trim[1], 0.02);
    }

    public function test_pdf_met_xref_stream(): void
    {
        $this->assertTrue((new MirroredBleedService)->canAddBleed(self::FIXTURE_XREFSTREAM));
        $result = (new PdfFormatValidator)->validate($this->metAfloop(self::FIXTURE_XREFSTREAM));

        $this->assertSame(['A4', true], [$result['format'], $result['has_bleed']]);
    }

    public function test_boekje_van_aangevulde_pdf_zet_snijkant_tegen_de_rug(): void
    {
        $pdf = (new BookletImpositionService)->impose($this->metAfloop($this->maakPdf(4)), 'A4', self::BLEED);
        $reader = new PdfReader(new ExtendedPdfParser(StreamReader::createByString($pdf)));

        $this->assertSame(2, $reader->getPageCount());
        // Linkerpagina: snijkant op de rug, afloop alleen aan de buitenkant
        preg_match('/1\.0+ 0 0 1\.0+ ([\d.]+) ([\d.]+) cm \/TPL\d+ Do/', $reader->getPage(1)->getContentStream(), $m);
        $spine = BookletImpositionService::SHEETS['A4']['width'] / 2;
        $this->assertEqualsWithDelta($spine - 595.28 - self::BLEED, (float) $m[1], 0.05);
    }

    public function test_links_mogen_formuliervelden_niet(): void
    {
        $service = new MirroredBleedService;

        $this->assertTrue($service->canAddBleed($this->maakPdf(1, link: true)), 'links worden niet gedrukt');
        $this->assertTrue($service->canAddBleed($this->handgemaakt()));
        $this->assertFalse($service->canAddBleed($this->handgemaakt(
            '/Annots [5 0 R]',
            '<< /Type /Annot /Subtype /Widget /FT /Tx /Rect [100 100 300 120] /V (Jan) >>'
        )), 'een ingevuld formulierveld zou verdwijnen');

        $kapot = $this->temp();
        file_put_contents($kapot, 'geen pdf');
        $this->assertFalse($service->canAddBleed($kapot));
    }

    public function test_te_grote_pdf_voor_het_geheugen_krijgt_geen_aanbod(): void
    {
        $this->assertTrue(MirroredBleedService::fitsInMemory(5 * 1024 ** 2, '256M'));
        $this->assertFalse(MirroredBleedService::fitsInMemory(100 * 1024 ** 2, '128M'), '100 MB past niet in 128 MB');
        $this->assertTrue(MirroredBleedService::fitsInMemory(100 * 1024 ** 2, '1G'));
        $this->assertTrue(MirroredBleedService::fitsInMemory(100 * 1024 ** 2, '-1'));
    }

    // ===== Bestelpad =====

    private function bestel(UploadedFile $pdf, array $overrides = [])
    {
        Http::fake(['api.mollie.com/*' => Http::response([
            'resource' => 'payment',
            'id' => 'tr_TestBleed01',
            'mode' => 'test',
            'status' => 'open',
            'amount' => ['value' => '10.00', 'currency' => 'EUR'],
            '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/test', 'type' => 'text/html']],
        ], 201)]);

        return $this->postJson('/api/order', array_merge([
            'pdf' => $pdf,
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
        ], $overrides));
    }

    private function upload(string $path): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('document.pdf', file_get_contents($path));
    }

    public function test_prijsberekening_meldt_of_de_rand_aangevuld_kan_worden(): void
    {
        $this->postJson('/api/calculate-price', ['pdf' => $this->upload($this->maakPdf())])
            ->assertOk()
            ->assertJson(['has_bleed' => false, 'bleed_fixable' => true]);

        $this->postJson('/api/calculate-price', ['pdf' => $this->upload($this->handgemaakt(
            '/Annots [5 0 R]',
            '<< /Type /Annot /Subtype /Widget /FT /Tx /Rect [100 100 300 120] >>'
        ))])
            ->assertOk()
            ->assertJson(['has_bleed' => false, 'bleed_fixable' => false]);

        $this->postJson('/api/calculate-price', ['pdf' => $this->upload($this->metAfloop($this->maakPdf()))])
            ->assertOk()
            ->assertJson(['has_bleed' => true, 'bleed_fixable' => false]);
    }

    public function test_bestelling_met_aangevulde_rand(): void
    {
        Storage::fake('local');

        $this->bestel($this->upload($this->maakPdf(2)), ['add_bleed' => '1'])->assertOk();

        $order = Order::sole();
        $this->assertTrue($order->has_bleed);
        $this->assertTrue($order->bleed_added);
        $this->assertSame(3, $order->bleed_mm);
        $this->assertSame('A4', $order->format);
        $this->assertNull($order->trim_width_mm);
        $this->assertSame('met 3 mm afloop (door ons aangevuld)', $order->bleed_label);

        // Het opgeslagen bestand is de versie met afloop
        $stored = Storage::disk('local')->path($order->pdf_path);
        $result = (new PdfFormatValidator)->validate($stored);
        $this->assertSame(['A4', true], [$result['format'], $result['has_bleed']]);
        $this->assertSame(2, $this->lees($stored)->getPageCount());
    }

    public function test_aanvullen_wordt_genegeerd_als_de_pdf_al_afloop_heeft(): void
    {
        Storage::fake('local');
        $metAfloop = $this->metAfloop($this->maakPdf(2));

        $this->bestel($this->upload($metAfloop), ['add_bleed' => '1'])->assertOk();

        $order = Order::sole();
        $this->assertTrue($order->has_bleed);
        $this->assertFalse($order->bleed_added);
        $this->assertSame(file_get_contents($metAfloop), Storage::disk('local')->get($order->pdf_path), 'origineel ongewijzigd opgeslagen');
    }

    public function test_zonder_keuze_geen_bestelling(): void
    {
        Storage::fake('local');

        $this->bestel($this->upload($this->maakPdf(2)), ['add_bleed' => '0', 'no_bleed_accepted' => '0'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('no_bleed_accepted');

        $this->assertSame(0, Order::count());
    }

    public function test_iets_kleiner_snijden_bewaart_het_origineel(): void
    {
        Storage::fake('local');
        $pdf = $this->maakPdf(2);

        $this->bestel($this->upload($pdf), ['add_bleed' => '0', 'no_bleed_accepted' => '1'])->assertOk();

        $order = Order::sole();
        $this->assertFalse($order->has_bleed);
        $this->assertFalse($order->bleed_added);
        $this->assertSame(file_get_contents($pdf), Storage::disk('local')->get($order->pdf_path));
    }

    public function test_server_weigert_aanvullen_als_dat_niet_kan(): void
    {
        Storage::fake('local');
        $formulier = $this->handgemaakt(
            '/Annots [5 0 R]',
            '<< /Type /Annot /Subtype /Widget /FT /Tx /Rect [100 100 300 120] /V (Jan) >>'
        );

        $this->bestel($this->upload($formulier), ['add_bleed' => '1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('add_bleed');

        $this->assertSame(0, Order::count());
    }

    public function test_mislukt_aanvullen_geeft_een_duidelijke_melding(): void
    {
        Storage::fake('local');
        $mock = $this->mock(MirroredBleedService::class);
        $mock->shouldReceive('canAddBleed')->andReturnTrue();
        $mock->shouldReceive('addBleed')
            ->andReturnUsing(function (string $in, string $out) {
                file_put_contents($out, 'half');
                throw new \RuntimeException('kapot');
            });

        $this->bestel($this->upload($this->maakPdf(2)), ['add_bleed' => '1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('add_bleed')
            ->assertJsonFragment(['message' => 'We konden de rand van je PDF niet automatisch aanvullen. Kies bovenaan "Iets kleiner snijden", of upload een PDF met afloop.']);

        $this->assertSame(0, Order::count());
        $this->assertSame([], Storage::disk('local')->allFiles('pdfs'), 'geen half bestand achtergelaten');
    }
}
