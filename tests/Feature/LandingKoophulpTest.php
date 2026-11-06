<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;

/**
 * Prijsvoorbeelden en voorwaarden op de landingspagina's komen uit de calculator
 * en de config, niet uit vaste tekst. Zo lopen ze niet uit de pas met de bestelling.
 */
class LandingKoophulpTest extends TestCase
{
    private static function euro(int $cents): string
    {
        return '€ '.number_format($cents / 100, 2, ',', '.');
    }

    public function test_boekje_maken_toont_totaalprijs_uit_de_calculator(): void
    {
        $html = $this->get('/boekje-maken')->assertOk()->getContent();

        $shipping = Order::calculatePrice(16, 'A5', 'booklet', 'shipping', 1);
        $pickup = Order::calculatePrice(16, 'A5', 'booklet', 'pickup', 1);

        $this->assertStringContainsString('Wat betaal je in totaal?', $html);
        $this->assertStringContainsString(self::euro($shipping['total']), $html);
        $this->assertStringContainsString(self::euro($pickup['total']), $html);
        $this->assertStringContainsString(self::euro($shipping['startup']), $html);

        foreach ([1, 2, 3, 5] as $n) {
            $total = Order::calculatePrice(16, 'A5', 'booklet', 'shipping', $n)['total'];
            $this->assertStringContainsString(self::euro($total), $html, "totaal voor {$n} exemplaren");
            $this->assertStringContainsString(self::euro((int) round($total / $n)), $html, "per exemplaar voor {$n}");
        }
    }

    public function test_tariefwijziging_werkt_direct_door(): void
    {
        config(['pricing.startup' => 1250, 'pricing.shipping' => 795]);

        $html = $this->get('/boekje-maken')->getContent();

        // 1250 + 16 × 10 + 500 + 795
        $this->assertStringContainsString('€ 27,05', $html);
        $this->assertStringNotContainsString('€ 24,10', $html);
    }

    public function test_boekje_maken_legt_toepassingen_en_keuze_uit(): void
    {
        $html = $this->get('/boekje-maken')->getContent();

        foreach (['Cadeau of verjaardagstijdschrift', 'Receptenboekje', 'Reisdagboek of fotoboekje', 'Geniet boekje of losse prints?', 'Enkelzijdig:', 'Dubbelzijdig:'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString("4 tot 64 pagina's", $html);
    }

    public function test_spoedvoorwaarden_staan_op_spoed_en_delft(): void
    {
        foreach (['/pdf-printen-met-spoed', '/printen-delft'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('Vandaag klaar: de voorwaarden', $html, $url);
            $this->assertStringContainsString('Vóór 11:00 op een werkdag besteld.', $html, $url);
            $this->assertStringContainsString('Tot en met 50 exemplaren', $html, $url);
            $this->assertStringContainsString('tussen 17:00 en 17:30', $html, $url);
            $this->assertStringContainsString('Verzending kost '.self::euro((int) config('pricing.shipping')).' per bestelling', $html, $url);
        }
    }

    public function test_cursusmateriaal_vergelijkt_groepsaantallen_tot_het_online_maximum(): void
    {
        $html = $this->get('/cursusmateriaal-printen')->assertOk()->getContent();

        $total = Order::calculatePrice(24, 'A4', 'booklet', 'shipping', 25)['total'];
        $this->assertStringContainsString(self::euro($total), $html);
        $this->assertStringContainsString('Tot '.config('pricing.max_quantity').' exemplaren online', $html);
    }

    public function test_knoppen_leiden_naar_bestaand_uploadanker(): void
    {
        $landing = $this->get('/boekje-maken')->getContent();
        $this->assertSame(3, substr_count($landing, 'data-cta='));
        $this->assertStringContainsString(url('/').'#upload', $landing);

        $this->assertStringContainsString('id="upload"', $this->get('/')->getContent());
    }
}
