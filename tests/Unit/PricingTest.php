<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;

/**
 * Toetst de prijsberekening als algoritme, met expliciet gezette tarieven.
 * Zo blijven deze tests geldig als de daadwerkelijke prijzen wijzigen.
 */
class PricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pricing.per_page_a4' => 15,
            'pricing.per_page_a5' => 10,
            'pricing.startup' => 1000,
            'pricing.binding' => 500,
            'pricing.binding_extra' => 250,
            'pricing.shipping' => 675,
            'pricing.promo_codes' => [
                'PMPNIEUW' => [
                    'discount_percent' => 25,
                    'applies_to' => ['pages', 'startup', 'binding'],
                    'active' => true,
                ],
                'INACTIEF' => [
                    'discount_percent' => 50,
                    'applies_to' => ['pages'],
                    'active' => false,
                ],
            ],
        ]);
    }

    public function test_basisbestelling_a4_boekje_met_verzending(): void
    {
        $p = Order::calculatePrice(100, 'A4', 'booklet', 'shipping', 1);

        $this->assertSame(1000, $p['startup']);
        $this->assertSame(1500, $p['pages']);      // 100 * 15
        $this->assertSame(500, $p['binding']);
        $this->assertSame(675, $p['shipping']);
        $this->assertSame(0, $p['discount']);
        $this->assertSame(3675, $p['total']);
    }

    public function test_a5_is_goedkoper_per_pagina_dan_a4(): void
    {
        $a4 = Order::calculatePrice(100, 'A4', 'booklet', 'shipping', 1);
        $a5 = Order::calculatePrice(100, 'A5', 'booklet', 'shipping', 1);

        $this->assertSame(1500, $a4['pages']);
        $this->assertSame(1000, $a5['pages']);     // 100 * 10
        $this->assertLessThan($a4['total'], $a5['total']);
    }

    public function test_losse_pagina_s_hebben_geen_inbindkosten(): void
    {
        $p = Order::calculatePrice(50, 'A4', 'loose', 'shipping', 1);

        $this->assertSame(0, $p['binding']);
        $this->assertSame(750 + 1000 + 675, $p['total']);
    }

    public function test_afhalen_kost_geen_verzendkosten(): void
    {
        $p = Order::calculatePrice(50, 'A4', 'loose', 'pickup', 1);

        $this->assertSame(0, $p['shipping']);
    }

    public function test_pagina_kosten_schalen_met_aantal_exemplaren(): void
    {
        $een = Order::calculatePrice(100, 'A4', 'loose', 'pickup', 1);
        $drie = Order::calculatePrice(100, 'A4', 'loose', 'pickup', 3);

        $this->assertSame(1500, $een['pages']);
        $this->assertSame(4500, $drie['pages']);
    }

    public function test_extra_boekjes_kosten_het_lagere_inbindtarief(): void
    {
        $p = Order::calculatePrice(10, 'A4', 'booklet', 'pickup', 3);

        // eerste boekje 500, twee extra a 250
        $this->assertSame(1000, $p['binding']);
    }

    public function test_verzendkosten_blijven_gelijk_ongeacht_aantal(): void
    {
        $een = Order::calculatePrice(10, 'A4', 'booklet', 'shipping', 1);
        $tien = Order::calculatePrice(10, 'A4', 'booklet', 'shipping', 10);

        $this->assertSame(675, $een['shipping']);
        $this->assertSame(675, $tien['shipping']);
    }

    public function test_promotiecode_geeft_korting_op_druk_start_en_afwerking(): void
    {
        $p = Order::calculatePrice(100, 'A4', 'booklet', 'shipping', 1, 'PMPNIEUW');

        // 25% over pages(1500) + startup(1000) + binding(500) = 375 + 250 + 125
        $this->assertSame(750, $p['discount']);
        $this->assertSame(3675 - 750, $p['total']);
    }

    public function test_promotiecode_geeft_geen_korting_op_verzendkosten(): void
    {
        $zonder = Order::calculatePrice(100, 'A4', 'booklet', 'pickup', 1, 'PMPNIEUW');
        $met = Order::calculatePrice(100, 'A4', 'booklet', 'shipping', 1, 'PMPNIEUW');

        $this->assertSame($zonder['discount'], $met['discount']);
    }

    public function test_onbekende_promotiecode_wordt_genegeerd(): void
    {
        $p = Order::calculatePrice(100, 'A4', 'booklet', 'shipping', 1, 'BESTAATNIET');

        $this->assertSame(0, $p['discount']);
    }

    public function test_inactieve_promotiecode_wordt_genegeerd(): void
    {
        $p = Order::calculatePrice(100, 'A4', 'booklet', 'shipping', 1, 'INACTIEF');

        $this->assertSame(0, $p['discount']);
    }

    public function test_totaal_is_altijd_de_som_van_de_onderdelen(): void
    {
        foreach ([[1,'A4','booklet','shipping',1,null], [250,'A5','loose','pickup',7,'PMPNIEUW']] as $args) {
            $p = Order::calculatePrice(...$args);
            $this->assertSame(
                $p['startup'] + $p['pages'] + $p['binding'] + $p['shipping'] - $p['discount'],
                $p['total'],
            );
        }
    }

    public function test_alle_bedragen_zijn_hele_centen(): void
    {
        $p = Order::calculatePrice(33, 'A4', 'booklet', 'shipping', 3, 'PMPNIEUW');

        foreach ($p as $key => $value) {
            $this->assertIsInt($value, "{$key} moet een geheel aantal centen zijn");
        }
    }
}
