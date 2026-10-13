<?php

namespace Tests\Unit;

use App\Services\PdfFormatValidator;
use FPDF;
use PHPUnit\Framework\TestCase;

class PdfFormatValidatorTest extends TestCase
{
    private function pdf(int $pages, string $format): string
    {
        // FPDF zet de MediaBox op de /Pages-root; pagina's erven hem via /Parent
        $pdf = new FPDF('P', 'mm', $format);
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage();
        }

        $path = tempnam(sys_get_temp_dir(), 'pmp');
        $pdf->Output('F', $path);

        return $path;
    }

    public function test_geerfde_mediabox_bij_veel_objecten(): void
    {
        // Met 10 pagina's bestaat object 11; het zoeken naar object 1 (/Pages) mocht dat niet raken
        foreach ([[1, 'A4'], [10, 'A4'], [24, 'A5'], [64, 'A4']] as [$pages, $format]) {
            $path = $this->pdf($pages, $format);
            $result = (new PdfFormatValidator())->validate($path);
            unlink($path);

            $this->assertTrue($result['valid'], "{$pages} pagina's {$format}: " . $result['reason']);
            $this->assertSame($format, $result['format']);
        }
    }

    public function test_grote_afbeeldingsstream_verbergt_de_paginas_niet(): void
    {
        // Canva-PDF's bevatten megabytes aan beelddata in één object; een regex die
        // per object tot "endobj" zoekt liep daarop vast (PCRE backtrack-limiet) en
        // keurde een gewoon A4-bestand af als "afwijkend formaat"
        $image = str_repeat('x', 3 * 1024 * 1024);
        $pdf = "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "4 0 obj\n<< /Type /XObject /Subtype /Image /Length " . strlen($image) . " >>\nstream\n{$image}\nendstream\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0.0 7.8299813 595.5 850.07996] >>\nendobj\n"
            . "%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'pmp');
        file_put_contents($path, $pdf);
        $result = (new PdfFormatValidator())->validate($path);
        unlink($path);

        $this->assertTrue($result['valid'], (string) $result['reason']);
        $this->assertSame('A4', $result['format']);
    }

    public function test_paginas_in_gecomprimeerde_object_stream(): void
    {
        // PDF 1.5+: de pagina-objecten staan in een ObjStm zonder "obj … endobj"
        $result = (new PdfFormatValidator())->validate(__DIR__ . '/../Fixtures/a4-6-paginas-xrefstream.pdf');

        $this->assertTrue($result['valid'], (string) $result['reason']);
        $this->assertSame('A4', $result['format']);
    }

    private function validate(string|array $format, string $orientation = 'P', int $pages = 1): array
    {
        $pdf = new FPDF($orientation, 'mm', $format);
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage();
        }
        $path = tempnam(sys_get_temp_dir(), 'pmp');
        $pdf->Output('F', $path);
        $result = (new PdfFormatValidator())->validate($path);
        unlink($path);

        return $result;
    }

    public function test_prijsklasse_is_kleinste_passende_formaat(): void
    {
        // [formaat, oriëntatie, prijsklasse, standaard, boekje mogelijk]
        $cases = [
            ['A4', 'P', 'A4', true, true],
            ['A5', 'P', 'A5', true, true],
            ['A5', 'L', 'A5', false, true],   // 210 × 148: twee naast elkaar = 420 mm, past
            ['A4', 'L', 'A4', false, false],  // 297 × 210: twee naast elkaar = 594 mm, past niet
            [[200, 200], 'P', 'A4', false, true],
            [[140, 140], 'P', 'A5', false, true],
            [[222, 304], 'P', 'A3', false, true],  // grootste boekjespagina
            [[230, 304], 'P', 'A3', false, false],
            ['A3', 'P', 'A3', false, false],
            ['A3', 'L', 'A3', false, false],
        ];

        foreach ($cases as [$format, $orientation, $class, $standard, $booklet]) {
            $label = json_encode($format) . " {$orientation}";
            $result = $this->validate($format, $orientation);

            $this->assertTrue($result['valid'], "{$label}: " . $result['reason']);
            $this->assertSame($class, $result['format'], $label);
            $this->assertSame($standard, $result['standard'], $label);
            $this->assertSame($booklet, $result['booklet_allowed'], $label);
        }
    }

    public function test_groter_dan_a3_wordt_afgekeurd(): void
    {
        $result = $this->validate([300, 430]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('groter dan A3', $result['reason']);
    }

    public function test_verschillende_formaten_in_een_pdf_worden_afgekeurd(): void
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->AddPage('P', [200, 280]);
        $path = tempnam(sys_get_temp_dir(), 'pmp');
        $pdf->Output('F', $path);
        $result = (new PdfFormatValidator())->validate($path);
        unlink($path);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('verschillende formaten', $result['reason']);
    }

    public function test_pdf_met_afloop_en_snijtekens_telt_het_snijformaat(): void
    {
        // Zoals een drukklare export (bv. Canva "Bijsnijmarkeringen en afloop"):
        // MediaBox met ruimte voor snijtekens, BleedBox 3 mm, TrimBox exact A4
        $pdf = "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 652.0 898.6] "
            . "/BleedBox [19.84 19.84 632.13 878.74] /TrimBox [28.35 28.35 623.63 870.24] >>\nendobj\n"
            . "%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'pmp');
        file_put_contents($path, $pdf);
        $result = (new PdfFormatValidator())->validate($path);
        unlink($path);

        $this->assertTrue($result['valid'], (string) $result['reason']);
        $this->assertSame('A4', $result['format']);
        $this->assertTrue($result['standard']);
        $this->assertSame('TrimBox', $result['box_used']);
        $this->assertTrue($result['has_bleed']);
        $this->assertSame(3.0, $result['bleed_mm']);
    }

    public function test_pdf_zonder_afloop(): void
    {
        $result = $this->validate('A4');

        $this->assertTrue($result['valid']);
        $this->assertFalse($result['has_bleed']);
        $this->assertSame(0.0, $result['bleed_mm']);
    }
}
