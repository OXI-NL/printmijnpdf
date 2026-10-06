<?php

namespace Tests\Unit;

use App\Pdf\StreamDecoder;
use App\Services\BookletImpositionService;
use PHPUnit\Framework\TestCase;

class BookletLayoutTest extends TestCase
{
    public function test_acht_paginas_geeft_twee_vellen_in_drukvolgorde(): void
    {
        $this->assertSame([
            [8, 1], [2, 7], // vel 1: voor, achter
            [6, 3], [4, 5], // vel 2: voor, achter
        ], BookletImpositionService::sheetLayout(8));
    }

    public function test_wordt_aangevuld_tot_een_veelvoud_van_vier(): void
    {
        // 10 pagina's → 12; nummers 11 en 12 blijven leeg
        $this->assertSame([
            [12, 1], [2, 11],
            [10, 3], [4, 9],
            [8, 5], [6, 7],
        ], BookletImpositionService::sheetLayout(10));
    }

    public function test_elke_pagina_komt_precies_een_keer_voor(): void
    {
        foreach ([1, 4, 5, 17, 48] as $count) {
            $pages = array_merge(...BookletImpositionService::sheetLayout($count));
            sort($pages);
            $this->assertSame(range(1, (int) ceil($count / 4) * 4), $pages, "bij {$count} pagina's");
        }
    }

    public function test_png_predictor_up_en_sub(): void
    {
        // Twee rijen van 3 bytes: rij 1 met Sub (type 1), rij 2 met Up (type 2)
        $encoded = "\x01" . "\x05\x01\x01" . "\x02" . "\x01\x01\x01";

        $this->assertSame("\x05\x06\x07" . "\x06\x07\x08", StreamDecoder::pngUnpredict($encoded, 1, 8, 3));
    }
}
