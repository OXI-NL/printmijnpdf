<?php

namespace App\Pdf;

use setasign\Fpdi\Fpdi as BaseFpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfReader\DataStructure\Rectangle;
use setasign\Fpdi\PdfReader\Page;
use setasign\Fpdi\PdfReader\PageBoundaries;

/**
 * FPDI met een parser die ook PDF 1.5+ (gecomprimeerde xref) kan lezen.
 */
class Fpdi extends BaseFpdi
{
    /**
     * Extra boxen per pagina, bv. ['TrimBox' => [x1, y1, x2, y2]] in punten.
     *
     * @var array<int, array<string, array{float, float, float, float}>>
     */
    protected array $pageBoxes = [];

    /** Pagina die _putpage() nu wegschrijft */
    protected ?int $writingPage = null;

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
     * Transformatiematrix (PDF-operator cm) voor alles tot het volgende endClip();
     * de verschuiving e/f in de huidige eenheid.
     */
    public function transform(float $a, float $b, float $c, float $d, float $e, float $f): void
    {
        $this->_out(sprintf('%.5F %.5F %.5F %.5F %.4F %.4F cm', $a, $b, $c, $d, $e * $this->k, $f * $this->k));
    }

    /**
     * Zet TrimBox, BleedBox enz. op de huidige pagina (punten, oorsprong linksonder).
     *
     * @param array<string, array{float, float, float, float}> $boxes
     */
    public function setPageBoxes(array $boxes): void
    {
        $this->pageBoxes[$this->page] = $boxes;
    }

    /**
     * Annotaties die meegedrukt worden (formuliervelden, stempels, opmerkingen)
     * gaan verloren als we een pagina importeren; links zijn alleen voor het scherm.
     */
    public function hasPrintableAnnotations(int $pageNumber): bool
    {
        $reader = $this->getPdfReader($this->currentReaderId);
        $parser = $reader->getParser();
        $annots = PdfType::resolve(PdfDictionary::get($reader->getPage($pageNumber)->getPageDictionary(), 'Annots'), $parser);

        if (!$annots instanceof PdfArray) {
            return false;
        }

        foreach ($annots->value as $entry) {
            $subtype = PdfType::resolve(PdfDictionary::get(PdfType::resolve($entry, $parser), 'Subtype'), $parser);
            if (!$subtype instanceof PdfName || $subtype->value !== 'Link') {
                return true;
            }
        }

        return false;
    }

    protected function _putpage($n)
    {
        $this->writingPage = $n;
        parent::_putpage($n);
        $this->writingPage = null;
    }

    /**
     * FPDF schrijft alleen een MediaBox; de extra boxen komen direct na /Parent.
     */
    protected function _put($s, $newLine = true)
    {
        parent::_put($s, $newLine);

        if ($s === '/Parent 1 0 R' && isset($this->pageBoxes[$this->writingPage])) {
            foreach ($this->pageBoxes[$this->writingPage] as $name => [$x1, $y1, $x2, $y2]) {
                parent::_put(sprintf('/%s [%.4F %.4F %.4F %.4F]', $name, $x1, $y1, $x2, $y2));
            }
        }
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

    /**
     * Een pagina uit het huidige bronbestand, zonder hem te importeren.
     */
    public function getSourcePage(int $pageNumber): Page
    {
        return $this->getPdfReader($this->currentReaderId)->getPage($pageNumber);
    }
}
