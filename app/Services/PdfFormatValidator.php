<?php

namespace App\Services;

use App\Pdf\Fpdi;
use Illuminate\Support\Facades\Log;

class PdfFormatValidator
{
    /**
     * Prijsklassen in PDF-punten (1 punt = 1/72 inch), staand gemeten.
     * Een pagina valt in de kleinste klasse waar hij (staand of liggend) in past.
     *
     * A5: 148 × 210 mm = 419.53 × 595.28 pt
     * A4: 210 × 297 mm = 595.28 × 841.89 pt
     * A3: 297 × 420 mm = 841.89 × 1190.55 pt (alleen losse pagina's)
     */
    public const FORMATS = [
        'A5' => ['width' => 419.53, 'height' => 595.28],
        'A4' => ['width' => 595.28, 'height' => 841.89],
        'A3' => ['width' => 841.89, 'height' => 1190.55],
    ];

    /**
     * Grootste pagina voor een geniet boekje: twee pagina's naast elkaar op het
     * drukvel van 460 × 320 mm met 8 mm marge rondom (444 × 304 mm bedrukbaar).
     */
    public const BOOKLET_MAX_WIDTH_MM = 222;
    public const BOOKLET_MAX_HEIGHT_MM = 304;

    /**
     * Tolerantie in punten voor afrondingsverschillen.
     * 5 pt ≈ 1.76 mm — ruim genoeg voor afrondingen in PDF-generators.
     */
    private const TOLERANCE_PT = 5.0;

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
     * Valideer of een PDF-bestand geprint kan worden: alle pagina's even groot
     * en hoogstens A3. Geeft de prijsklasse terug en of een boekje kan.
     *
     * @param string $filePath Pad naar het PDF-bestand
     * @return array{valid: bool, format: ?string, standard: bool, booklet_allowed: bool, reason: ?string, detected_width_mm: ?float, detected_height_mm: ?float, box_used: ?string}
     */
    public function validate(string $filePath): array
    {
        try {
            $content = file_get_contents($filePath);

            if ($content === false) {
                return $this->reject('Kon het PDF-bestand niet lezen.');
            }

            // Decomprimeer FlateDecode-streams zodat objecten in gecomprimeerde
            // object streams (ObjStm) ook zichtbaar worden voor de regex-parser.
            $content = $this->decompressStreams($content);

            // Haal daarna de ruwe streamdata weg: de regexes hieronder lezen per
            // object tot "endobj" en lopen anders vast op megabytes beelddata
            // (PCRE backtrack-limiet), waardoor er "geen pagina's" gevonden worden
            $content = $this->stripStreamData($content);

            $pages = $this->extractPages($content);
            $results = [];
            foreach ($pages as $index => $pageContent) {
                $results[] = $this->analyzePage($pageContent, $content, $index);
            }

            // Bij PDF 1.5+ staan pagina-objecten vaak gecomprimeerd in een object
            // stream, zonder "obj … endobj" eromheen; dan leest FPDI de boxen uit
            if (empty($results)) {
                $results = $this->analyzePagesWithFpdi($filePath);
            }

            if (empty($results)) {
                return $this->reject('Kon geen pagina\'s vinden in het PDF-bestand.');
            }

            foreach ($results as $index => $result) {
                if ($result === null) {
                    return $this->reject(
                        'Kon het formaat van pagina ' . ($index + 1) . ' niet bepalen.'
                    );
                }
            }

            [$boxUsed, $width, $height] = $results[0];
            $widthMm = self::toMm($width);
            $heightMm = self::toMm($height);

            // Eén formaat per bestelling
            foreach ($results as $index => [, $w, $h]) {
                if (abs($w - $width) > self::TOLERANCE_PT || abs($h - $height) > self::TOLERANCE_PT) {
                    return $this->reject(
                        "Je PDF bevat pagina's van verschillende formaten: pagina 1 is {$widthMm} × {$heightMm} mm, "
                            . 'pagina ' . ($index + 1) . ' is ' . self::toMm($w) . ' × ' . self::toMm($h) . ' mm. '
                            . 'Per bestelling printen we één formaat.',
                        $widthMm,
                        $heightMm,
                        $boxUsed
                    );
                }
            }

            $format = $this->priceClass($width, $height);
            if ($format === null) {
                return $this->reject(
                    "Je pagina's zijn {$widthMm} × {$heightMm} mm, groter dan A3 (297 × 420 mm).",
                    $widthMm,
                    $heightMm,
                    $boxUsed
                );
            }

            $tolMm = self::toMm(self::TOLERANCE_PT);

            return [
                'valid' => true,
                'format' => $format,
                'standard' => $this->isStandard($width, $height),
                'booklet_allowed' => $widthMm <= self::BOOKLET_MAX_WIDTH_MM + $tolMm
                    && $heightMm <= self::BOOKLET_MAX_HEIGHT_MM + $tolMm,
                'reason' => null,
                'detected_width_mm' => $widthMm,
                'detected_height_mm' => $heightMm,
                'box_used' => $boxUsed,
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
            $dimensions = $this->extractBox($pageContent, $boxName, $fullContent);

            // Als de box niet direct op de pagina staat, zoek in het parent Pages-object
            if ($dimensions === null) {
                $dimensions = $this->extractBoxFromParent($pageContent, $fullContent, $boxName);
            }

            if ($dimensions === null) {
                continue;
            }

            return $this->classify($dimensions[0], $dimensions[1], $boxName, $rotation);
        }

        return null;
    }

    /**
     * Zelfde analyse als analyzePage(), maar met de boxen zoals FPDI ze leest
     * (inclusief gecomprimeerde object streams en overgeërfde boxen).
     *
     * @return array<int, array|null>
     */
    private function analyzePagesWithFpdi(string $filePath): array
    {
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($filePath);
        } catch (\Throwable $e) {
            Log::warning('PdfFormatValidator: FPDI kon het bestand niet lezen: ' . $e->getMessage());
            return [];
        }

        $results = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $page = $pdf->getSourcePage($i);
            $rotation = ((int) $page->getRotation() % 360 + 360) % 360;
            $result = null;

            foreach (self::BOX_PRIORITY as $boxName) {
                $box = $page->getBoundary($boxName, false);
                if ($box !== false) {
                    $result = $this->classify($box->getWidth(), $box->getHeight(), $boxName, $rotation);
                    break;
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Afmetingen van één pagina zoals hij gedrukt wordt (na afloopcorrectie en rotatie).
     *
     * @return array [boxUsed, width, height]
     */
    private function classify(float $width, float $height, string $boxName, int $rotation): array
    {
        // Corrigeer BleedBox: trek typische afloop af als er geen TrimBox/CropBox was
        if ($boxName === 'BleedBox') {
            $width -= 2 * self::TYPICAL_BLEED_PT;
            $height -= 2 * self::TYPICAL_BLEED_PT;
        }

        // Pas rotatie toe (90° of 270° draait breedte/hoogte om)
        if ($rotation === 90 || $rotation === 270) {
            [$width, $height] = [$height, $width];
        }

        return [$boxName, $width, $height];
    }

    /**
     * Haal box-dimensies op uit page content.
     * Ondersteunt zowel inline arrays als indirecte referenties.
     *
     * @return array|null [width, height]
     */
    private function extractBox(string $content, string $boxName, string $fullContent = ''): ?array
    {
        // Inline array: /TrimBox [0 0 419.53 595.28]
        $pattern = '/\/' . $boxName . '\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/';

        if (preg_match($pattern, $content, $matches)) {
            $x1 = floatval($matches[1]);
            $y1 = floatval($matches[2]);
            $x2 = floatval($matches[3]);
            $y2 = floatval($matches[4]);

            return [abs($x2 - $x1), abs($y2 - $y1)];
        }

        // Indirecte referentie: /TrimBox 15 0 R
        if ($fullContent !== '') {
            $refPattern = '/\/' . $boxName . '\s+(\d+)\s+0\s+R/';
            if (preg_match($refPattern, $content, $refMatch)) {
                $objId = $refMatch[1];
                $objPattern = '/(?<!\d)' . $objId . '\s+0\s+obj\s*(.*?)endobj/s';
                if (preg_match($objPattern, $fullContent, $objMatch)) {
                    $arrayPattern = '/\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/';
                    if (preg_match($arrayPattern, $objMatch[1], $arrMatch)) {
                        return [abs(floatval($arrMatch[3]) - floatval($arrMatch[1])),
                                abs(floatval($arrMatch[4]) - floatval($arrMatch[2]))];
                    }
                }
            }
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
            $pattern = '/(?<!\d)' . $parentId . '\s+0\s+obj\s*(.*?)endobj/s';
            if (preg_match($pattern, $fullContent, $parentObj)) {
                return $this->extractBox($parentObj[1], $boxName, $fullContent);
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
     * Kleinste prijsklasse waar de pagina (staand of liggend) in past, of null als hij groter is dan A3.
     */
    private function priceClass(float $width, float $height): ?string
    {
        $short = min($width, $height);
        $long = max($width, $height);

        foreach (self::FORMATS as $name => $dims) {
            if ($short <= $dims['width'] + self::TOLERANCE_PT && $long <= $dims['height'] + self::TOLERANCE_PT) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Precies staand A4 of A5: daarvoor is geen snijmaat nodig op pakbon en factuur.
     */
    private function isStandard(float $width, float $height): bool
    {
        foreach (['A4', 'A5'] as $name) {
            $dims = self::FORMATS[$name];
            if (abs($width - $dims['width']) <= self::TOLERANCE_PT && abs($height - $dims['height']) <= self::TOLERANCE_PT) {
                return true;
            }
        }

        return false;
    }

    private static function toMm(float $pt): float
    {
        return round($pt / 72 * 25.4, 1);
    }

    /**
     * Decomprimeer alle FlateDecode-streams in de PDF.
     * Hierdoor worden objecten in gecomprimeerde object streams (ObjStm)
     * zichtbaar voor de regex-parser.
     */
    private function decompressStreams(string $content): string
    {
        // Zoek FlateDecode-streams en vervang ze door gedecomprimeerde versies
        $result = $content;

        if (preg_match_all('/(\d+\s+0\s+obj\s*<<.*?>>)\s*stream\r?\n/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $i => $match) {
                $streamStart = $match[1] + strlen($match[0]);
                $header = $matches[1][$i][0];

                // Alleen FlateDecode-streams decomprimeren
                if (strpos($header, '/FlateDecode') === false) {
                    continue;
                }

                // Zoek het einde van de stream
                $endPos = strpos($content, "\nendstream", $streamStart);
                if ($endPos === false) {
                    $endPos = strpos($content, "\r\nendstream", $streamStart);
                }
                if ($endPos === false) {
                    continue;
                }

                $streamData = substr($content, $streamStart, $endPos - $streamStart);

                // Probeer te decomprimeren
                $decompressed = @gzuncompress($streamData);
                if ($decompressed === false) {
                    // Probeer met raw deflate (sommige PDF's gebruiken dit)
                    $decompressed = @gzinflate($streamData);
                }

                if ($decompressed !== false) {
                    // Voeg gedecomprimeerde inhoud toe aan het resultaat
                    // zodat de regex-parser de objecten erin kan vinden
                    $result .= "\n" . $decompressed;
                }
            }
        }

        return $result;
    }

    /**
     * Verwijder de inhoud tussen "stream" en "endstream". De dictionaries
     * (met /Type, /MediaBox enz.) blijven staan; alleen de binaire data gaat eruit.
     */
    private function stripStreamData(string $content): string
    {
        $result = '';
        $offset = 0;

        while (($start = strpos($content, 'stream', $offset)) !== false) {
            // Alleen echte streams: die beginnen direct na de dictionary (">>"),
            // dus "endstream" en losse tekst met het woord "stream" overslaan
            if (!str_ends_with(rtrim(substr($content, max(0, $start - 32), min($start, 32))), '>>')) {
                $offset = $start + 6;
                continue;
            }

            $end = strpos($content, 'endstream', $start + 6);
            if ($end === false) {
                break;
            }

            $result .= substr($content, $offset, $start + 6 - $offset) . "\n";
            $offset = $end;
        }

        return $result . substr($content, $offset);
    }

    /**
     * Bouw een afkeurresultaat.
     */
    private function reject(string $reason, ?float $widthMm = null, ?float $heightMm = null, ?string $boxUsed = null): array
    {
        return [
            'valid' => false,
            'format' => null,
            'standard' => false,
            'booklet_allowed' => false,
            'reason' => $reason,
            'detected_width_mm' => $widthMm,
            'detected_height_mm' => $heightMm,
            'box_used' => $boxUsed,
        ];
    }
}
