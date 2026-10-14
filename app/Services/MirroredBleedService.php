<?php

namespace App\Services;

use App\Pdf\Fpdi;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\PdfReader\PageBoundaries;

/**
 * Maakt afloop voor een PDF zonder afloop: elke pagina blijft 100% op haar
 * plek en krijgt rondom een strook met het spiegelbeeld van haar eigen rand.
 * Een foto, kleurvlak of verloop loopt zo naadloos over de snijlijn door.
 * Alles blijft vector; er wordt niets gerasterd.
 */
class MirroredBleedService
{
    public const BLEED_MM = 3;

    /**
     * Kan deze PDF automatisch afloop krijgen? Nee als FPDI hem niet kan lezen
     * (bv. beveiligd) of als er formuliervelden of andere meegedrukte
     * annotaties op staan: die zouden bij het importeren verdwijnen.
     */
    public function canAddBleed(string $inputPath): bool
    {
        // FPDF bouwt de hele uitvoer in het geheugen op (gemeten: ±1,4× de bestandsgrootte);
        // een tekort is een fatale fout die we bij het bestellen niet meer kunnen opvangen
        if (!self::fitsInMemory((int) @filesize($inputPath))) {
            Log::info('MirroredBleed: PDF te groot voor het geheugen, geen automatische afloop');
            return false;
        }

        try {
            $pdf = new Fpdi('P', 'pt');
            $pageCount = $pdf->setSourceFile($inputPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                if ($pdf->hasPrintableAnnotations($i)) {
                    Log::info("MirroredBleed: pagina {$i} heeft formuliervelden of annotaties, geen automatische afloop");
                    return false;
                }
                $pdf->importPage($i, PageBoundaries::TRIM_BOX);
            }

            return $pageCount > 0;
        } catch (\Throwable $e) {
            Log::info('MirroredBleed: PDF niet bruikbaar voor automatische afloop: ' . $e->getMessage());
            return false;
        }
    }

    public static function fitsInMemory(int $fileSize, ?string $limit = null): bool
    {
        $limit ??= (string) ini_get('memory_limit');
        if ($limit === '' || $limit === '-1') {
            return true;
        }

        $bytes = (int) $limit;
        $bytes *= match (strtoupper(substr(trim($limit), -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        return memory_get_usage(true) + 1.6 * $fileSize + 16 * 1024 ** 2 < $bytes;
    }

    /**
     * Schrijf een kopie van de PDF met afloop rondom. Het snijformaat (TrimBox)
     * is het formaat van de oorspronkelijke pagina, de MediaBox en BleedBox
     * zijn rondom BLEED_MM groter.
     */
    public function addBleed(string $inputPath, string $outputPath): void
    {
        $bleed = self::BLEED_MM * 72 / 25.4;

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('PrintMijnPDF');
        $pageCount = $pdf->setSourceFile($inputPath);

        if ($pageCount === 0) {
            throw new \RuntimeException('PDF heeft geen pagina\'s');
        }

        for ($i = 1; $i <= $pageCount; $i++) {
            // Zelfde snijformaat als de formaatcontrole: TrimBox, anders CropBox, anders MediaBox.
            // FPDI draait een pagina met /Rotate al rechtop.
            $template = $pdf->importPage($i, PageBoundaries::TRIM_BOX);
            $size = $pdf->getTemplateSize($template);
            [$w, $h] = [$size['width'], $size['height']];
            [$fullW, $fullH] = [$w + 2 * $bleed, $h + 2 * $bleed];

            $pdf->AddPage($fullW > $fullH ? 'L' : 'P', [$fullW, $fullH]);
            $pdf->setPageBoxes([
                'BleedBox' => [0, 0, $fullW, $fullH],
                'TrimBox' => [$bleed, $bleed, $bleed + $w, $bleed + $h],
            ]);

            // Negen vakken: de pagina zelf in het midden, gespiegelde stroken
            // langs de randen en dubbel gespiegelde hoekjes
            foreach ([-1, 0, 1] as $col) {
                foreach ([-1, 0, 1] as $row) {
                    $this->placeTile($pdf, $template, $col, $row, $w, $h, $bleed);
                }
            }
        }

        $pdf->Output('F', $outputPath);
    }

    /**
     * Eén vak: $col -1/0/1 = links/midden/rechts, $row -1/0/1 = onder/midden/boven
     * (PDF-coördinaten, oorsprong linksonder). Een randvak spiegelt de pagina
     * in de snijlijn aan die kant en knipt af op de afloopstrook.
     */
    protected function placeTile(Fpdi $pdf, string $template, int $col, int $row, float $w, float $h, float $bleed): void
    {
        [$clipX, $clipW, $scaleX, $shiftX] = match ($col) {
            -1 => [0, $bleed, -1, 2 * $bleed],
            0 => [$bleed, $w, 1, 0],
            1 => [$bleed + $w, $bleed, -1, 2 * ($bleed + $w)],
        };
        [$clipY, $clipH, $scaleY, $shiftY] = match ($row) {
            -1 => [0, $bleed, -1, 2 * $bleed],
            0 => [$bleed, $h, 1, 0],
            1 => [$bleed + $h, $bleed, -1, 2 * ($bleed + $h)],
        };

        $pdf->startClip($clipX, $clipY, $clipW, $clipH);
        $pdf->transform($scaleX, 0, 0, $scaleY, $shiftX, $shiftY);
        // Bovenkant van de pagina ligt $bleed onder de bovenrand van het vel
        $pdf->useTemplate($template, $bleed, $bleed, $w, $h);
        $pdf->endClip();
    }
}
