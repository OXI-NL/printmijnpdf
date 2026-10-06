<?php

namespace App\Pdf;

use setasign\Fpdi\PdfParser\PdfParser;

class ExtendedPdfParser extends PdfParser
{
    public function getCrossReference()
    {
        if ($this->xref === null) {
            $this->xref = new ExtendedCrossReference($this, $this->resolveFileHeader());
        }

        return $this->xref;
    }
}
