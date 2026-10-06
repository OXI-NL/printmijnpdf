<?php

namespace App\Services;

use App\Pdf\Fpdi;

class PdfPageCounter
{
    /**
     * Tel de pagina's van een PDF. Via de paginaboom (FPDI); lukt dat niet
     * (bv. een beveiligde PDF), dan via de oude regex-telling.
     */
    public function count(string $path): int
    {
        try {
            return (new Fpdi())->setSourceFile($path);
        } catch (\Throwable) {
            return $this->countByRegex((string) file_get_contents($path));
        }
    }

    private function countByRegex(string $content): int
    {
        $pageCount = preg_match_all("/\/Page\W/", $content);
        if ($pageCount === 0) {
            $pageCount = preg_match_all("/\/Type\s*\/Page[^s]/", $content);
        }

        return max(1, (int) $pageCount);
    }
}
