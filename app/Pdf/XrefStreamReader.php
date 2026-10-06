<?php

namespace App\Pdf;

use setasign\Fpdi\PdfParser\CrossReference\ReaderInterface;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfStream;

/**
 * Leest een cross-reference stream (PDF 1.5+).
 *
 * Entries: [0, ...] vrij, [1, offset, gen] gewoon object,
 * [2, objectstreamnummer, index] object in een object stream.
 */
class XrefStreamReader implements ReaderInterface
{
    /** @var array<int, array{int, int, int}> */
    protected array $entries = [];

    protected PdfDictionary $trailer;

    public function __construct(PdfStream $stream)
    {
        $this->trailer = $stream->value;

        $widths = array_map(
            fn ($value) => (int) $value->value,
            PdfArray::ensure(PdfDictionary::get($stream->value, 'W'))->value
        );
        $size = (int) PdfDictionary::get($stream->value, 'Size')->value;

        $index = PdfDictionary::get($stream->value, 'Index');
        $sections = $index instanceof PdfArray
            ? array_map(fn ($value) => (int) $value->value, $index->value)
            : [0, $size];

        $data = StreamDecoder::decode($stream);
        $rowLength = array_sum($widths);
        $pos = 0;

        for ($s = 0; $s + 1 < count($sections); $s += 2) {
            for ($n = 0; $n < $sections[$s + 1]; $n++) {
                if ($pos + $rowLength > strlen($data)) {
                    break 2;
                }

                $fields = [];
                foreach ($widths as $field => $width) {
                    $value = 0;
                    for ($b = 0; $b < $width; $b++) {
                        $value = ($value << 8) | ord($data[$pos++]);
                    }
                    // Een veld met breedte 0 krijgt de standaardwaarde (type 1, verder 0)
                    $fields[$field] = $width === 0 && $field === 0 ? 1 : $value;
                }

                $this->entries[$sections[$s] + $n] = [$fields[0], $fields[1] ?? 0, $fields[2] ?? 0];
            }
        }
    }

    /**
     * @return array{int, int, int}|null
     */
    public function getEntry(int $objectNumber): ?array
    {
        return $this->entries[$objectNumber] ?? null;
    }

    public function getOffsetFor($objectNumber)
    {
        $entry = $this->getEntry((int) $objectNumber);

        return $entry !== null && $entry[0] === 1 ? $entry[1] : false;
    }

    public function getTrailer()
    {
        return $this->trailer;
    }
}
