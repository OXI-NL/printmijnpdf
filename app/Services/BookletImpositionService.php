<?php

namespace App\Services;

use App\Models\Order;
use App\Pdf\Fpdi;
use setasign\Fpdi\PdfReader\PageBoundaries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BookletImpositionService
{
    /**
     * Drukvellen in punten, liggend: twee pagina's naast elkaar rond de rug.
     * SRA3 (450 × 320 mm) voor A4-boekjes, SRA4 (320 × 225 mm) voor A5.
     */
    public const SHEETS = [
        'A4' => ['name' => 'SRA3', 'width' => 1275.59, 'height' => 907.09],
        'A5' => ['name' => 'SRA4', 'width' => 907.09, 'height' => 637.80],
    ];

    /** Snijtekens: afstand tot het snijkader en lengte, in mm. */
    public const CROP_MARK_OFFSET_MM = 3;
    public const CROP_MARK_LENGTH_MM = 7;

    /**
     * Maak impositie voor een order
     * 
     * @param Order $order
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function createImposition(Order $order): array
    {
        // Alleen voor boekjes
        if ($order->binding_type !== 'booklet') {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Order is geen boekje'
            ];
        }

        // Check of input PDF bestaat
        $inputPath = Storage::disk('local')->path($order->pdf_path);
        if (!file_exists($inputPath)) {
            Log::error("Imposition: Input PDF niet gevonden voor order {$order->order_number}");
            return [
                'success' => false,
                'path' => null,
                'message' => 'Input PDF niet gevonden'
            ];
        }

        $outputFilename = pathinfo($order->pdf_stored_name, PATHINFO_FILENAME) . '_imposed.pdf';
        $relativePath = 'pdfs/imposed/' . $outputFilename;

        try {
            $bleedPt = $order->has_bleed ? (float) $order->bleed_mm * 72 / 25.4 : 0.0;
            Storage::disk('local')->makeDirectory('pdfs/imposed');
            $this->impose(
                $inputPath,
                self::sheetFor($order),
                $bleedPt,
                Storage::disk('local')->path($relativePath)
            );
        } catch (\Throwable $e) {
            Log::error("Imposition failed for order {$order->order_number}: " . $e->getMessage());
            return [
                'success' => false,
                'path' => null,
                'message' => 'PDF kon niet worden verwerkt: ' . $e->getMessage()
            ];
        }

        // Update order met imposed path
        $order->update([
            'pdf_imposed_path' => $relativePath
        ]);

        Log::info("Imposition created for order {$order->order_number}: {$relativePath}");

        return [
            'success' => true,
            'path' => $relativePath,
            'message' => 'Impositie succesvol aangemaakt'
        ];
    }

    /**
     * Welk vel uit SHEETS: SRA4 alleen als de pagina op een halve SRA4 past
     * (staand A5 of kleiner); al het andere, ook liggend A5, gaat op SRA3.
     */
    public static function sheetFor(Order $order): string
    {
        $halfSra4Mm = self::SHEETS['A5']['width'] / 2 / 72 * 25.4;

        if ($order->format !== 'A5' || ($order->trim_width_mm !== null && $order->trim_width_mm > $halfSra4Mm - 6)) {
            return 'A4';
        }

        return 'A5';
    }

    /**
     * Zet een PDF om naar drukvellen voor een geniet boekje (saddle stitch).
     * Elk vel heeft een voor- en achterkant met twee pagina's tegen de rug.
     *
     * @param float $bleedPt Afloop rondom in punten, gebruikt als de PDF geen TrimBox heeft
     * @param string|null $outputPath Schrijf naar dit bestand; zonder pad komt de PDF als string terug
     */
    public function impose(string $inputPath, string $format, float $bleedPt = 0.0, ?string $outputPath = null): string
    {
        $sheet = self::SHEETS[$format];

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('PrintMijnPDF');
        $pageCount = $pdf->setSourceFile($inputPath);

        if ($pageCount === 0) {
            throw new \RuntimeException('PDF heeft geen pagina\'s');
        }

        foreach (self::sheetLayout($pageCount) as $side) {
            $pdf->AddPage('L', [$sheet['height'], $sheet['width']]);

            [$left, $right] = $side;
            $trims = [
                'left' => $left <= $pageCount ? $this->placePage($pdf, $left, $sheet, true, $bleedPt) : null,
                'right' => $right <= $pageCount ? $this->placePage($pdf, $right, $sheet, false, $bleedPt) : null,
            ];
            $this->drawCropMarks($pdf, $sheet, $trims);
        }

        if ($outputPath !== null) {
            // Direct naar schijf: voorkomt een tweede kopie van grote PDF's in het geheugen
            $pdf->Output('F', $outputPath);

            return $outputPath;
        }

        return $pdf->Output('S');
    }

    /**
     * Paginavolgorde per velzijde, aangevuld tot een veelvoud van 4 met lege
     * pagina's (nummers boven het aantal pagina's blijven leeg).
     *
     * @return array<int, array{int, int}> [links, rechts] per zijde: voor, achter, voor, ...
     */
    public static function sheetLayout(int $pageCount): array
    {
        $total = (int) ceil($pageCount / 4) * 4;
        $sides = [];

        for ($sheet = 0; $sheet < $total / 4; $sheet++) {
            $sides[] = [$total - 2 * $sheet, 2 * $sheet + 1];      // voorkant
            $sides[] = [2 * $sheet + 2, $total - 2 * $sheet - 1];  // achterkant
        }

        return $sides;
    }

    /**
     * Plaats één pagina op 100% met de snijkant tegen de rug, verticaal
     * gecentreerd. Afloop blijft aan de buitenkanten staan; aan de rugkant
     * wordt afgeknipt zodat de pagina's elkaar niet overlappen.
     *
     * @return array{x: float, y: float, width: float, height: float} Snijkader op het vel (oorsprong linksonder)
     */
    protected function placePage(Fpdi $pdf, int $pageNumber, array $sheet, bool $isLeft, float $bleedPt): array
    {
        $half = $sheet['width'] / 2;

        [$template, $trim] = $this->importWithTrim($pdf, $pageNumber, $bleedPt);
        $size = $pdf->getTemplateSize($template);

        // Alleen verkleinen als de pagina niet past (de validatie laat voor boekjes max. 222 × 304 mm toe)
        $scale = min(1.0, $half / $trim['width'], $sheet['height'] / $trim['height']);

        // Snijkader op het vel (PDF-coördinaten, oorsprong linksonder)
        $trimWidth = $trim['width'] * $scale;
        $trimHeight = $trim['height'] * $scale;
        $trimX = $isLeft ? $half - $trimWidth : $half;
        $trimY = ($sheet['height'] - $trimHeight) / 2;

        // Template zo plaatsen dat het snijkader op die plek valt
        $x = $trimX - $trim['x'] * $scale;
        $bottom = $trimY - $trim['y'] * $scale;
        $height = $size['height'] * $scale;
        $topDown = $sheet['height'] - ($bottom + $height);

        $pdf->startClip($isLeft ? 0 : $half, 0, $half, $sheet['height']);
        $pdf->useTemplate($template, $x, $topDown, $size['width'] * $scale, $height);
        $pdf->endClip();

        return ['x' => $trimX, 'y' => $trimY, 'width' => $trimWidth, 'height' => $trimHeight];
    }

    /**
     * Snijtekens op de hoeken van het snijkader, op CROP_MARK_OFFSET_MM ervan
     * af: horizontaal en verticaal aan de buitenkanten, verticaal (vouwlijn)
     * boven en onder de rug. Een lege helft (aanvulpagina) krijgt de maat van
     * de pagina ernaast, gespiegeld in de rug.
     *
     * @param array{left: ?array, right: ?array} $trims Snijkaders uit placePage()
     */
    protected function drawCropMarks(Fpdi $pdf, array $sheet, array $trims): void
    {
        $half = $sheet['width'] / 2;
        $trims['left'] ??= $trims['right'] === null ? null : ['x' => $half - $trims['right']['width']] + $trims['right'];
        $trims['right'] ??= $trims['left'] === null ? null : ['x' => $half] + $trims['left'];

        $offset = self::CROP_MARK_OFFSET_MM * 72 / 25.4;
        $length = self::CROP_MARK_LENGTH_MM * 72 / 25.4;

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.25);

        // FPDF tekent van boven naar beneden; $sheet['height'] - y zet om
        $line = fn (float $x1, float $y1, float $x2, float $y2) =>
            $pdf->Line($x1, $sheet['height'] - $y1, $x2, $sheet['height'] - $y2);

        foreach ($trims as $side => $trim) {
            if ($trim === null) {
                continue;
            }

            $outerX = $side === 'left' ? $trim['x'] : $trim['x'] + $trim['width'];
            $dir = $side === 'left' ? -1 : 1;
            $top = $trim['y'] + $trim['height'];
            $bottom = $trim['y'];

            foreach ([$bottom, $top] as $y) {
                $line($outerX + $dir * $offset, $y, $outerX + $dir * ($offset + $length), $y);
            }
            // Vouwlijn boven en onder de rug één keer, bij de linkerpagina
            foreach ($side === 'left' ? [$outerX, $half] : [$outerX] as $x) {
                $line($x, $top + $offset, $x, $top + $offset + $length);
                $line($x, $bottom - $offset, $x, $bottom - $offset - $length);
            }
        }
    }

    /**
     * Importeer een pagina inclusief afloop en bepaal waar het snijkader in
     * de template ligt (x/y vanaf linksonder, in punten).
     *
     * @return array{0: string, 1: array{x: float, y: float, width: float, height: float}}
     */
    protected function importWithTrim(Fpdi $pdf, int $pageNumber, float $bleedPt): array
    {
        // BleedBox valt terug op CropBox en MediaBox; snijtekens in de slug vallen zo buiten beeld
        $template = $pdf->importPage($pageNumber, PageBoundaries::BLEED_BOX);
        $size = $pdf->getTemplateSize($template);
        $page = $pdf->getImportedPageInfo($pageNumber, PageBoundaries::BLEED_BOX);

        // Expliciete TrimBox (niet-gedraaid): exact snijkader binnen de geïmporteerde box
        if ($page['trim'] !== null && $page['rotation'] === 0) {
            return [$template, [
                'x' => $page['trim']->getLlx() - $page['box']->getLlx(),
                'y' => $page['trim']->getLly() - $page['box']->getLly(),
                'width' => $page['trim']->getWidth(),
                'height' => $page['trim']->getHeight(),
            ]];
        }

        // Geen TrimBox maar wel afloop opgegeven: snijkader ligt die afstand naar binnen
        if ($bleedPt > 0 && $size['width'] > 2 * $bleedPt && $size['height'] > 2 * $bleedPt) {
            return [$template, [
                'x' => $bleedPt,
                'y' => $bleedPt,
                'width' => $size['width'] - 2 * $bleedPt,
                'height' => $size['height'] - 2 * $bleedPt,
            ]];
        }

        return [$template, ['x' => 0.0, 'y' => 0.0, 'width' => $size['width'], 'height' => $size['height']]];
    }

    /**
     * Check of impositie al bestaat voor een order
     */
    public function hasImposition(Order $order): bool
    {
        if (empty($order->pdf_imposed_path)) {
            return false;
        }

        return Storage::disk('local')->exists($order->pdf_imposed_path);
    }

    /**
     * Haal pad naar geïmponeerde PDF op
     */
    public function getImposedPath(Order $order): ?string
    {
        if (!$this->hasImposition($order)) {
            return null;
        }

        return Storage::disk('local')->path($order->pdf_imposed_path);
    }

    /**
     * Verwijder impositie voor een order
     */
    public function deleteImposition(Order $order): bool
    {
        if (empty($order->pdf_imposed_path)) {
            return true;
        }

        if (Storage::disk('local')->exists($order->pdf_imposed_path)) {
            Storage::disk('local')->delete($order->pdf_imposed_path);
        }

        $order->update(['pdf_imposed_path' => null]);
        
        return true;
    }
}
