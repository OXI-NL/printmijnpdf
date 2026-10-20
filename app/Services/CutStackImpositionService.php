<?php

namespace App\Services;

use App\Pdf\Fpdi;
use App\Services\Concerns\ImposesPages;

/**
 * Inslag voor losbladig werk als cut & stack: meerdere pagina's per liggend
 * SRA3-vel, zo verdeeld dat na het snijden elke stapel een doorlopend stuk
 * is. Leg stapel 1 op stapel 2, die op stapel 3, enz. en alles ligt op
 * volgorde. Stapels zijn genummerd in leesvolgorde (linksboven eerst).
 *
 * Dubbelzijdig keert het vel over de korte kant (omslaan als een boek, net
 * als bij de boekjes): op de achterkant wisselen links en rechts van plaats.
 */
class CutStackImpositionService
{
    use ImposesPages;

    /** Liggend SRA3, net als de A4-boekjes */
    public const SHEET = BookletImpositionService::SHEETS['A4'];

    /** Hoogstens zoveel pagina's per vel: A4 komt 2-op, A5 4-op */
    public const MAX_UP = 4;

    /** Ruimte tussen de pagina's (dubbel snijden, 3 mm afloop per kant) */
    public const GAP_MM = 6;

    /** Minimale rand rond het raster, voor snijtekens en de opdrachtregel */
    public const MIN_MARGIN_MM = 8;

    /**
     * @param bool $duplex Dubbelzijdig (4/4) of enkelzijdig (4/0)
     * @param float $bleedPt Afloop rondom in punten, gebruikt als de PDF geen TrimBox heeft
     * @param string|null $outputPath Schrijf naar dit bestand; zonder pad komt de PDF als string terug
     * @param string $orderNumber Komt in de opdrachtregel onder de snijtekens
     */
    public function impose(string $inputPath, bool $duplex, float $bleedPt = 0.0, ?string $outputPath = null, string $orderNumber = ''): string
    {
        $sheet = self::SHEET;

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('PrintMijnPDF');
        $pdf->SetFont('Helvetica', '', self::SLUG_FONT_SIZE);
        $pageCount = $pdf->setSourceFile($inputPath);

        if ($pageCount === 0) {
            throw new \RuntimeException('PDF heeft geen pagina\'s');
        }

        // Alle pagina's zijn even groot (PdfFormatValidator): de eerste bepaalt het raster
        [, $trim] = $this->importWithTrim($pdf, 1, $bleedPt);
        $grid = self::grid($trim['width'], $trim['height']);

        $sides = self::sheetLayout($pageCount, $grid['cols'], $grid['rows'], $duplex);
        $sheets = $duplex ? intdiv(count($sides), 2) : count($sides);
        $bottomLeft = ($grid['rows'] - 1) * $grid['cols'];

        foreach ($sides as $i => $positions) {
            $front = !$duplex || $i % 2 === 0;
            $pdf->AddPage('L', [$sheet['height'], $sheet['width']]);

            foreach ($positions as $position => $page) {
                if ($page <= $pageCount) {
                    $this->placePage($pdf, $page, $grid, self::cell($grid, $position), $front, $bleedPt);
                }
            }

            $this->drawCropMarks($pdf, $grid);
            $this->drawSlug($pdf, $sheet['height'], self::cell($grid, $bottomLeft), self::slugText(
                $orderNumber, 'Losbladig', $duplex, $duplex ? intdiv($i, 2) + 1 : $i + 1, $sheets, $duplex ? $front : null
            ));
        }

        if ($outputPath !== null) {
            $pdf->Output('F', $outputPath);

            return $outputPath;
        }

        return $pdf->Output('S');
    }

    /**
     * Paginanummer per plek op het vel, per velzijde. Plekken tellen in
     * leesvolgorde (linksboven = 0); nummers boven het aantal pagina's
     * blijven leeg.
     *
     * Enkelzijdig, S vellen: plek p op vel s krijgt pagina p·S + s + 1.
     * Dubbelzijdig gaat het om bladen (voor- en achterkant): blad p·S + s
     * staat op de voorkant op plek p en op de achterkant gespiegeld
     * (links ↔ rechts), zodat het na het keren over de korte kant precies
     * achter zijn voorkant valt.
     *
     * @return array<int, array<int, int>> voor, (achter,) voor, ...
     */
    public static function sheetLayout(int $pageCount, int $cols, int $rows, bool $duplex): array
    {
        $up = $cols * $rows;
        $leaves = $duplex ? (int) ceil($pageCount / 2) : $pageCount;
        $sheets = (int) ceil($leaves / $up);
        $sides = [];

        for ($s = 0; $s < $sheets; $s++) {
            $front = $back = [];
            for ($p = 0; $p < $up; $p++) {
                $leaf = $p * $sheets + $s;
                if (!$duplex) {
                    $front[$p] = $leaf + 1;
                    continue;
                }
                $front[$p] = 2 * $leaf + 1;
                $back[intdiv($p, $cols) * $cols + ($cols - 1 - $p % $cols)] = 2 * $leaf + 2;
            }
            $sides[] = $front;
            if ($duplex) {
                ksort($back);
                $sides[] = $back;
            }
        }

        return $sides;
    }

    /**
     * Raster voor pagina's van $width × $height punt (snijmaat) op het vel:
     * zoveel mogelijk pagina's (hoogstens MAX_UP), bij gelijkspel recht
     * boven gedraaid. Gedraaid staat de kop naar links (voorkant) of rechts
     * (achterkant). Past zelfs één pagina niet, dan wordt hij verkleind.
     *
     * @return array{cols: int, rows: int, rotated: bool, scale: float, cellWidth: float, cellHeight: float, gap: float, x: float, y: float}
     */
    public static function grid(float $width, float $height): array
    {
        $sheet = self::SHEET;
        $gap = self::mm(self::GAP_MM);
        $usableWidth = $sheet['width'] - 2 * self::mm(self::MIN_MARGIN_MM);
        $usableHeight = $sheet['height'] - 2 * self::mm(self::MIN_MARGIN_MM);
        $best = null;

        foreach ([false, true] as $rotated) {
            [$w, $h] = $rotated ? [$height, $width] : [$width, $height];
            for ($cols = 1; $cols <= self::MAX_UP; $cols++) {
                for ($rows = 1; $cols * $rows <= self::MAX_UP; $rows++) {
                    $fits = $cols * $w + ($cols - 1) * $gap <= $usableWidth + 0.5
                        && $rows * $h + ($rows - 1) * $gap <= $usableHeight + 0.5;
                    if ($fits && ($best === null || $cols * $rows > $best['cols'] * $best['rows'])) {
                        $best = ['cols' => $cols, 'rows' => $rows, 'rotated' => $rotated, 'scale' => 1.0];
                    }
                }
            }
        }

        if ($best === null) {
            $upright = min($usableWidth / $width, $usableHeight / $height);
            $turned = min($usableWidth / $height, $usableHeight / $width);
            $best = ['cols' => 1, 'rows' => 1, 'rotated' => $turned > $upright, 'scale' => max($upright, $turned)];
        }

        [$w, $h] = $best['rotated'] ? [$height, $width] : [$width, $height];
        $best['cellWidth'] = $w * $best['scale'];
        $best['cellHeight'] = $h * $best['scale'];
        $best['gap'] = $gap;
        // Linksonder van het raster, gecentreerd op het vel
        $best['x'] = ($sheet['width'] - $best['cols'] * $best['cellWidth'] - ($best['cols'] - 1) * $gap) / 2;
        $best['y'] = ($sheet['height'] - $best['rows'] * $best['cellHeight'] - ($best['rows'] - 1) * $gap) / 2;

        return $best;
    }

    /**
     * Snijkader van plek $position (leesvolgorde) op het vel.
     *
     * @return array{x: float, y: float, width: float, height: float} Oorsprong linksonder
     */
    public static function cell(array $grid, int $position): array
    {
        $col = $position % $grid['cols'];
        $row = intdiv($position, $grid['cols']);

        return [
            'x' => $grid['x'] + $col * ($grid['cellWidth'] + $grid['gap']),
            'y' => $grid['y'] + ($grid['rows'] - 1 - $row) * ($grid['cellHeight'] + $grid['gap']),
            'width' => $grid['cellWidth'],
            'height' => $grid['cellHeight'],
        ];
    }

    /**
     * Plaats één pagina met het snijkader op $cell. De afloop loopt door tot
     * halverwege de tussenruimte en wordt daar afgeknipt.
     */
    protected function placePage(Fpdi $pdf, int $pageNumber, array $grid, array $cell, bool $front, float $bleedPt): void
    {
        [$template, $trim] = $this->importWithTrim($pdf, $pageNumber, $bleedPt);
        $size = $pdf->getTemplateSize($template);
        $s = $grid['scale'];

        // Matrix van template (oorsprong linksonder) naar vel; gedraaid: kop links op de voorkant, rechts op de achterkant
        if (!$grid['rotated']) {
            $matrix = [$s, 0, 0, $s, $cell['x'] - $s * $trim['x'], $cell['y'] - $s * $trim['y']];
        } elseif ($front) {
            $matrix = [0, $s, -$s, 0, $cell['x'] + $s * ($trim['y'] + $trim['height']), $cell['y'] - $s * $trim['x']];
        } else {
            $matrix = [0, -$s, $s, 0, $cell['x'] - $s * $trim['y'], $cell['y'] + $s * ($trim['x'] + $trim['width'])];
        }

        $bleed = $grid['gap'] / 2;
        $pdf->startClip($cell['x'] - $bleed, $cell['y'] - $bleed, $cell['width'] + 2 * $bleed, $cell['height'] + 2 * $bleed);
        $pdf->transform(...$matrix);
        $pdf->useTemplate($template, 0, self::SHEET['height'] - $size['height'], $size['width'], $size['height']);
        $pdf->endClip();
    }

    /**
     * Snijtekens in de rand rond het raster: voor elke snijlijn één teken
     * aan beide kanten, CROP_MARK_OFFSET_MM van het raster af. Waar de rand
     * krap is (A5 4-op) worden ze korter.
     */
    protected function drawCropMarks(Fpdi $pdf, array $grid): void
    {
        $sheet = self::SHEET;
        $offset = self::mm(BookletImpositionService::CROP_MARK_OFFSET_MM);
        $maxLength = self::mm(BookletImpositionService::CROP_MARK_LENGTH_MM);

        $left = $grid['x'];
        $right = $sheet['width'] - $grid['x'];
        $bottom = $grid['y'];
        $top = $sheet['height'] - $grid['y'];
        // Minstens 2 mm wit tot de rand van het vel
        $verticalLength = min($maxLength, $bottom - $offset - self::mm(2));
        $horizontalLength = min($maxLength, $left - $offset - self::mm(2));

        $xs = $ys = [];
        for ($col = 0; $col < $grid['cols']; $col++) {
            $xs[] = $left + $col * ($grid['cellWidth'] + $grid['gap']);
            $xs[] = end($xs) + $grid['cellWidth'];
        }
        for ($row = 0; $row < $grid['rows']; $row++) {
            $ys[] = $bottom + $row * ($grid['cellHeight'] + $grid['gap']);
            $ys[] = end($ys) + $grid['cellHeight'];
        }

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.25);

        // FPDF tekent van boven naar beneden; $sheet['height'] - y zet om
        $line = fn (float $x1, float $y1, float $x2, float $y2) =>
            $pdf->Line($x1, $sheet['height'] - $y1, $x2, $sheet['height'] - $y2);

        foreach ($xs as $x) {
            $line($x, $top + $offset, $x, $top + $offset + $verticalLength);
            $line($x, $bottom - $offset, $x, $bottom - $offset - $verticalLength);
        }
        foreach ($ys as $y) {
            $line($left - $offset, $y, $left - $offset - $horizontalLength, $y);
            $line($right + $offset, $y, $right + $offset + $horizontalLength, $y);
        }
    }
}
