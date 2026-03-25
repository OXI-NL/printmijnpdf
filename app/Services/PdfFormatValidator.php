<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PdfFormatValidator
{
    /**
     * Toegestane formaten in PDF-punten (1 punt = 1/72 inch).
     *
     * A4 staand: 210 × 297 mm = 595.28 × 841.89 pt
     * A5 staand: 148 × 210 mm = 419.53 × 595.28 pt
     */
    private const FORMATS = [
        'A4' => ['width' => 595.28, 'height' => 841.89],
        'A5' => ['width' => 419.53, 'height' => 595.28],
    ];

    /**
     * Tolerantie in punten voor afrondingsverschillen.
     * 5 pt ≈ 1.76 mm — ruim genoeg voor afrondingen in PDF-generators,
     * maar klein genoeg om andere formaten (b.v. Letter, A3) uit te sluiten.
     */
    private const TOLERANCE_PT = 5.0;

    /**
     * Typische afloop (bleed) in punten.
     * 3 mm = 8.504 pt — standaard in drukwerk.
     * Wordt gebruikt om BleedBox te corrigeren wanneer er geen TrimBox/CropBox is.
     */
    private const TYPICAL_BLEED_PT = 8.504;

    /**
     * Prioriteitsvolgorde voor PDF-boxes:
     *
     * 1. TrimBox  — het uiteindelijke snijformaat na afwerking (meest betrouwbaar)
     * 2. CropBox  — het zichtbare gebied na cropping (goede fallback)
     * 3. BleedBox — snijformaat + afloop; wordt gecorrigeerd met typische bleed
     * 4. MediaBox — het volledige "canvas" van de pagina (minst specifiek)
     *
     * Bij drukwerk-PDF's met snijtekens bevat de MediaBox vaak extra ruimte
     * voor snijmarkeringen, waardoor die groter is dan het eindformaat.
     * Daarom is TrimBox de beste indicator van het werkelijke formaat.
     */
    private const BOX_PRIORITY = ['TrimBox', 'CropBox', 'BleedBox', 'MediaBox'];

    /**
     * Valideer of een PDF-bestand uitsluitend staand A4 of staand A5 is.
     *
     * @param string $filePath Pad naar het PDF-bestand
     * @return array{valid: bool, format: ?string, reason: ?string, detected_width_mm: ?float, detected_height_mm: ?float, box_used: ?string}
     */
    public function validate(string $filePath): array
    {
        try {
            $content = file_get_contents($filePath);

            if ($content === false) {
                return $this->reject('Kon het PDF-bestand niet lezen.');
            }

            $pages = $this->extractPages($content);

            if (empty($pages)) {
                return $this->reject('Kon geen pagina\'s vinden in het PDF-bestand.');
            }

            $detectedFormat = null;
            $lastBoxUsed = null;
            $lastWidth = null;
            $lastHeight = null;

            foreach ($pages as $index => $pageContent) {
                $result = $this->analyzePage($pageContent, $content, $index);

                if ($result === null) {
                    return $this->reject(
                        'Kon het formaat van pagina ' . ($index + 1) . ' niet bepalen.'
                    );
                }

                [$format, $boxUsed, $width, $height] = $result;

                if ($format === null) {
                    $widthMm = round($width / 72 * 25.4, 1);
                    $heightMm = round($height / 72 * 25.4, 1);
                    return $this->reject(
                        "Pagina " . ($index + 1) . " heeft een afwijkend formaat ({$widthMm} × {$heightMm} mm).",
                        $widthMm,
                        $heightMm,
                        $boxUsed
                    );
                }

                // Alle pagina's moeten hetzelfde formaat hebben
                if ($detectedFormat !== null && $detectedFormat !== $format) {
                    return $this->reject(
                        "Pagina " . ($index + 1) . " is {$format} maar eerdere pagina's zijn {$detectedFormat}."
                    );
                }

                $detectedFormat = $format;
                $lastBoxUsed = $boxUsed;
                $lastWidth = $width;
                $lastHeight = $height;
            }

            $widthMm = round($lastWidth / 72 * 25.4, 1);
            $heightMm = round($lastHeight / 72 * 25.4, 1);

            return [
                'valid' => true,
                'format' => $detectedFormat,
                'reason' => null,
                'detected_width_mm' => $widthMm,
                'detected_height_mm' => $heightMm,
                'box_used' => $lastBoxUsed,
            ];
        } catch (\Exception $e) {
            Log::error('PdfFormatValidator error: ' . $e->getMessage());
            return $this->reject('Er ging iets mis bij het analyseren van het PDF-formaat.');
        }
    }

    /**
     * Splits de PDF-inhoud in individuele page-objecten.
     */
    private function extractPages(string $content): array
    {
        $pages = [];

        // Zoek alle Page-objecten (niet Pages)
        // Patroon: "X 0 obj" gevolgd door content met /Type /Page (maar niet /Pages)
        if (preg_match_all('/(\d+)\s+0\s+obj\s*(.*?)endobj/s', $content, $objMatches, PREG_SET_ORDER)) {
            foreach ($objMatches as $obj) {
                $objContent = $obj[2];
                // Check of het een Page-object is (niet Pages)
                if (preg_match('/\/Type\s*\/Page(?!s)\b/', $objContent)) {
                    $pages[] = $objContent;
                }
            }
        }

        return $pages;
    }

    /**
     * Analyseer een enkele pagina en bepaal het formaat.
     *
     * @return array|null [format, boxUsed, width, height] of null als niets gevonden
     */
    private function analyzePage(string $pageContent, string $fullContent, int $pageIndex): ?array
    {
        // Detecteer rotatie op de pagina
        $rotation = $this->detectRotation($pageContent);

        // Probeer boxes in prioriteitsvolgorde
        foreach (self::BOX_PRIORITY as $boxName) {
            $dimensions = $this->extractBox($pageContent, $boxName);

            // Als de box niet direct op de pagina staat, zoek in het parent Pages-object
            if ($dimensions === null && $boxName === 'MediaBox') {
                $dimensions = $this->extractBoxFromParent($pageContent, $fullContent, $boxName);
            }

            if ($dimensions === null) {
                continue;
            }

            [$width, $height] = $dimensions;

            // Corrigeer BleedBox: trek typische afloop af als er geen TrimBox/CropBox was
            if ($boxName === 'BleedBox') {
                $width -= 2 * self::TYPICAL_BLEED_PT;
                $height -= 2 * self::TYPICAL_BLEED_PT;
            }

            // Pas rotatie toe (90° of 270° draait breedte/hoogte om)
            if ($rotation === 90 || $rotation === 270) {
                [$width, $height] = [$height, $width];
            }

            // Check of het staand is (hoogte > breedte)
            if ($width > $height) {
                // Liggend formaat → niet toegestaan
                $format = $this->matchFormat($height, $width);
                return [$this->rejectIfLandscape($format), $boxName, $width, $height];
            }

            $format = $this->matchFormat($width, $height);
            return [$format, $boxName, $width, $height];
        }

        return null;
    }

    /**
     * Haal box-dimensies op uit page content.
     *
     * @return array|null [width, height]
     */
    private function extractBox(string $content, string $boxName): ?array
    {
        $pattern = '/\/' . $boxName . '\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/';

        if (preg_match($pattern, $content, $matches)) {
            $x1 = floatval($matches[1]);
            $y1 = floatval($matches[2]);
            $x2 = floatval($matches[3]);
            $y2 = floatval($matches[4]);

            return [abs($x2 - $x1), abs($y2 - $y1)];
        }

        return null;
    }

    /**
     * Zoek een box in het parent Pages-object (voor gedeelde MediaBox).
     */
    private function extractBoxFromParent(string $pageContent, string $fullContent, string $boxName): ?array
    {
        // Zoek het parent-reference
        if (preg_match('/\/Parent\s+(\d+)\s+0\s+R/', $pageContent, $parentMatch)) {
            $parentId = $parentMatch[1];
            // Zoek het parent object
            $pattern = '/' . $parentId . '\s+0\s+obj\s*(.*?)endobj/s';
            if (preg_match($pattern, $fullContent, $parentObj)) {
                return $this->extractBox($parentObj[1], $boxName);
            }
        }

        return null;
    }

    /**
     * Detecteer de /Rotate waarde van een pagina.
     */
    private function detectRotation(string $pageContent): int
    {
        if (preg_match('/\/Rotate\s+(\d+)/', $pageContent, $matches)) {
            return intval($matches[1]) % 360;
        }

        return 0;
    }

    /**
     * Match breedte en hoogte (in staande oriëntatie) tegen bekende formaten.
     *
     * @param float $width  Breedte in punten (kleinste zijde)
     * @param float $height Hoogte in punten (grootste zijde)
     * @return string|null Formaatcode ('A4', 'A5') of null
     */
    private function matchFormat(float $width, float $height): ?string
    {
        foreach (self::FORMATS as $name => $dims) {
            if (
                abs($width - $dims['width']) <= self::TOLERANCE_PT &&
                abs($height - $dims['height']) <= self::TOLERANCE_PT
            ) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Liggend formaat is niet toegestaan, zelfs als de afmetingen A4/A5 matchen.
     */
    private function rejectIfLandscape(?string $format): ?string
    {
        // Retourneer null om aan te geven dat het formaat niet is toegestaan
        return null;
    }

    /**
     * Bouw een afkeurresultaat.
     */
    private function reject(string $reason, ?float $widthMm = null, ?float $heightMm = null, ?string $boxUsed = null): array
    {
        return [
            'valid' => false,
            'format' => null,
            'reason' => $reason,
            'detected_width_mm' => $widthMm,
            'detected_height_mm' => $heightMm,
            'box_used' => $boxUsed,
        ];
    }
}
