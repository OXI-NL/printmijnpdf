<?php

namespace App\Pdf;

use setasign\Fpdi\Fpdi as BaseFpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\DataStructure\Rectangle;
use setasign\Fpdi\PdfReader\PageBoundaries;

/**
 * FPDI met een parser die ook PDF 1.5+ (gecomprimeerde xref) kan lezen.
 */
class Fpdi extends BaseFpdi
{
    protected function getPdfParserInstance(StreamReader $streamReader, array $parserParams = [])
    {
        return new ExtendedPdfParser($streamReader);
    }

    /**
     * Knip alles wat tot endClip() getekend wordt af op deze rechthoek
     * (PDF-coördinaten in de huidige eenheid, oorsprong linksonder).
     */
    public function startClip(float $x, float $y, float $w, float $h): void
    {
        $k = $this->k;
        $this->_out(sprintf('q %.2F %.2F %.2F %.2F re W n', $x * $k, $y * $k, $w * $k, $h * $k));
    }

    public function endClip(): void
    {
        $this->_out('Q');
    }

    /**
     * Boxen en rotatie van een pagina uit het huidige bronbestand.
     *
     * @return array{box: Rectangle, trim: Rectangle|null, rotation: int}
     */
    public function getImportedPageInfo(int $pageNumber, string $box): array
    {
        $page = $this->getPdfReader($this->currentReaderId)->getPage($pageNumber);
        $trim = $page->getBoundary(PageBoundaries::TRIM_BOX, false);

        return [
            'box' => $page->getBoundary($box),
            'trim' => $trim === false ? null : $trim,
            'rotation' => ((int) $page->getRotation() % 360 + 360) % 360,
        ];
    }
}
