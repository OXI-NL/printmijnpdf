<?php

namespace App\Pdf;

use setasign\Fpdi\PdfParser\Filter\Flate;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfStream;

/**
 * Decodeert xref- en object streams, inclusief de PNG-predictor die de gratis
 * FPDI-parser niet ondersteunt (die zit alleen in de betaalde add-on).
 */
class StreamDecoder
{
    public static function decode(PdfStream $stream): string
    {
        $params = PdfDictionary::get($stream->value, 'DecodeParms');
        if ($params instanceof PdfArray) {
            $params = $params->value[0] ?? null;
        }

        $predictor = $params instanceof PdfDictionary
            ? (int) PdfDictionary::get($params, 'Predictor')->value
            : 0;

        if ($predictor <= 1) {
            return $stream->getUnfilteredStream();
        }

        $filters = $stream->getFilters();
        if (count($filters) !== 1 || !($filters[0] instanceof PdfName) || !in_array($filters[0]->value, ['FlateDecode', 'Fl'], true)) {
            throw new PdfParserException('Niet-ondersteunde filtercombinatie met predictor.');
        }

        $data = (new Flate())->decode($stream->getStream());

        if ($predictor < 10) {
            throw new PdfParserException("Niet-ondersteunde predictor {$predictor}.");
        }

        $colors = (int) (PdfDictionary::get($params, 'Colors')->value ?? 1) ?: 1;
        $bits = (int) (PdfDictionary::get($params, 'BitsPerComponent')->value ?? 8) ?: 8;
        $columns = (int) (PdfDictionary::get($params, 'Columns')->value ?? 1) ?: 1;

        return self::pngUnpredict($data, $colors, $bits, $columns);
    }

    /**
     * PNG-predictor (PDF Predictor >= 10): elke rij begint met een filterbyte.
     */
    public static function pngUnpredict(string $data, int $colors, int $bits, int $columns): string
    {
        $bpp = max(1, intdiv($colors * $bits, 8));
        $rowLength = (int) ceil($colors * $bits * $columns / 8);
        $previous = array_fill(0, $rowLength, 0);
        $output = '';

        for ($pos = 0, $len = strlen($data); $pos + 1 + $rowLength <= $len; $pos += 1 + $rowLength) {
            $type = ord($data[$pos]);
            $row = array_values(unpack('C*', substr($data, $pos + 1, $rowLength)));

            for ($i = 0; $i < $rowLength; $i++) {
                $left = $i >= $bpp ? $row[$i - $bpp] : 0;
                $up = $previous[$i];
                $upLeft = $i >= $bpp ? $previous[$i - $bpp] : 0;

                $row[$i] = ($row[$i] + match ($type) {
                    0 => 0,
                    1 => $left,
                    2 => $up,
                    3 => intdiv($left + $up, 2),
                    4 => self::paeth($left, $up, $upLeft),
                    default => throw new PdfParserException("Onbekend PNG-filtertype {$type}."),
                }) & 0xFF;
            }

            $output .= pack('C*', ...$row);
            $previous = $row;
        }

        return $output;
    }

    private static function paeth(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);

        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        }

        return $pb <= $pc ? $b : $c;
    }
}
