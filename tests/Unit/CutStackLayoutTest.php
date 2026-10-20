<?php

namespace Tests\Unit;

use App\Services\CutStackImpositionService as CutStack;
use PHPUnit\Framework\TestCase;

class CutStackLayoutTest extends TestCase
{
    public function test_enkelzijdig_elke_stapel_is_een_doorlopend_stuk(): void
    {
        // 7 pagina's 2-op → 4 vellen; links 1–4, rechts 5–7 (8 blijft leeg)
        $this->assertSame([
            [1, 5], [2, 6], [3, 7], [4, 8],
        ], CutStack::sheetLayout(7, 2, 1, false));
    }

    public function test_dubbelzijdig_achterkant_gespiegeld_achter_de_voorkant(): void
    {
        // 8 pagina's 2-op = 4 bladen → 2 vellen; stapel links 1–4, rechts 5–8
        $this->assertSame([
            [1, 5], [6, 2], // vel 1: voor, achter (links ↔ rechts)
            [3, 7], [8, 4], // vel 2
        ], CutStack::sheetLayout(8, 2, 1, true));
    }

    public function test_dubbelzijdig_4_op_spiegelt_per_rij(): void
    {
        $sides = CutStack::sheetLayout(16, 2, 2, true);

        $this->assertSame([1, 5, 9, 13], $sides[0]);
        $this->assertSame([6, 2, 14, 10], $sides[1]);
    }

    public function test_stapels_op_elkaar_geven_de_paginavolgorde(): void
    {
        foreach ([[1, 2, 1], [9, 2, 1], [10, 2, 2], [23, 2, 2]] as [$count, $cols, $rows]) {
            foreach ([false, true] as $duplex) {
                $sides = CutStack::sheetLayout($count, $cols, $rows, $duplex);
                $fronts = $duplex ? array_values(array_filter($sides, fn ($k) => $k % 2 === 0, ARRAY_FILTER_USE_KEY)) : $sides;

                // Voorkanten per stapel achter elkaar gelezen
                $order = [];
                for ($p = 0; $p < $cols * $rows; $p++) {
                    foreach ($fronts as $side) {
                        $order[] = $side[$p];
                    }
                }
                $step = $duplex ? 2 : 1;
                $this->assertSame(range(1, count($order) * $step, $step), $order, "{$count} p., {$cols}×{$rows}, " . ($duplex ? '4/4' : '4/0'));

                if ($duplex) {
                    // Elke achterkant ligt achter zijn eigen voorkant, gespiegeld in de rij
                    foreach (array_chunk($sides, 2) as [$front, $back]) {
                        foreach ($front as $p => $page) {
                            $mirror = intdiv($p, $cols) * $cols + ($cols - 1 - $p % $cols);
                            $this->assertSame($page + 1, $back[$mirror]);
                        }
                    }
                }
            }
        }
    }

    public function test_raster_per_formaat(): void
    {
        $pt = fn (float $mm) => $mm * 72 / 25.4;

        $a4 = CutStack::grid($pt(210), $pt(297));
        $this->assertSame([2, 1, false], [$a4['cols'], $a4['rows'], $a4['rotated']], 'A4 2-op recht');

        $a5 = CutStack::grid($pt(148), $pt(210));
        $this->assertSame([2, 2, true], [$a5['cols'], $a5['rows'], $a5['rotated']], 'A5 4-op gedraaid');

        $a5liggend = CutStack::grid($pt(210), $pt(148));
        $this->assertSame([2, 2, false], [$a5liggend['cols'], $a5liggend['rows'], $a5liggend['rotated']], 'liggend A5 4-op recht');

        $a3 = CutStack::grid($pt(297), $pt(420));
        $this->assertSame([1, 1, 1.0], [$a3['cols'], $a3['rows'], $a3['scale']], 'A3 1-op op ware grootte');
    }
}
