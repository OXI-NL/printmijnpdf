<?php

namespace App\Pdf;

use setasign\Fpdi\PdfParser\CrossReference\ReaderInterface;

/**
 * Hybride bestand: een klassieke xref-tabel met een /XRefStm-aanvulling.
 * De tabel gaat voor, de stream vult aan.
 */
class HybridXrefReader implements ReaderInterface
{
    public function __construct(
        protected ReaderInterface $table,
        protected XrefStreamReader $stream,
    ) {
    }

    /**
     * @return array{int, int, int}|null
     */
    public function getEntry(int $objectNumber): ?array
    {
        $offset = $this->table->getOffsetFor($objectNumber);
        if ($offset !== false) {
            return [1, $offset, 0];
        }

        return $this->stream->getEntry($objectNumber);
    }

    public function getOffsetFor($objectNumber)
    {
        $entry = $this->getEntry((int) $objectNumber);

        return $entry !== null && $entry[0] === 1 ? $entry[1] : false;
    }

    public function getTrailer()
    {
        return $this->table->getTrailer();
    }
}
