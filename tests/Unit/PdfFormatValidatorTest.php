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
}
