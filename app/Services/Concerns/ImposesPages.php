<?php

namespace App\Services\Concerns;

use App\Pdf\Fpdi;
use setasign\Fpdi\PdfReader\PageBoundaries;

/**
 * Gedeeld door de inslag van boekjes en losbladig werk.
 */
trait ImposesPages
{
    /** Opdrachtregel onder de snijtekens: afstand tot het snijkader en lettergrootte. */
    public const SLUG_OFFSET_MM = 4;
    public const SLUG_FONT_SIZE = 6;

    protected static function mm(float $mm): float
    {
        return $mm * 72 / 25.4;
    }

    /**
     * Regel met ordernummer, product, kleuren en velnummer, bv.
     * "PMP-261019-684E | Geniete brochure | 4/4 | vel 3/12 voorkant".
     */
    protected static function slugText(string $orderNumber, string $product, bool $duplex, int $sheet, int $sheets, ?bool $front): string
    {
        $vel = "vel {$sheet}/{$sheets}" . ($front === null ? '' : ($front ? ' voorkant' : ' achterkant'));

        return implode(' | ', array_filter([$orderNumber, $product, $duplex ? '4/4' : '4/0', $vel]));
    }

    /**
     * Zet de opdrachtregel onder een snijkader, met de bovenkant van de tekst
     * SLUG_OFFSET_MM onder de snijlijn (buiten de afloop van 3 mm) en net
     * rechts van het snijteken op de linkerhoek.
     *
     * @param array{x: float, y: float, width: float, height: float} $trim Snijkader op het vel (oorsprong linksonder)
     */
    protected function drawSlug(Fpdi $pdf, float $sheetHeight, array $trim, string $text): void
    {
        // Bovenkant van de hoofdletters ligt 0,718 × de korpsgrootte boven de basislijn (Helvetica)
        $baseline = $trim['y'] - self::mm(self::SLUG_OFFSET_MM) - 0.718 * self::SLUG_FONT_SIZE;

        $pdf->SetTextColor(0);
        $pdf->Text($trim['x'] + self::mm(2), $sheetHeight - $baseline, $text);
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
}
