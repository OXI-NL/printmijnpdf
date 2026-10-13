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
}
