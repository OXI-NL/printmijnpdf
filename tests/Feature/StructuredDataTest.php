<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * JSON-LD structured data (schema.org) op de pagina's die Google indexeert.
 *
 * Deze data stond centraal in de @context-bug: de sleutel "@context" botste
 * met een nieuwe Blade-directive. Een pagina kan 200 teruggeven terwijl de
 * structured data stilletjes stuk of verdwenen is, dus die controleren we apart.
 */
class StructuredDataTest extends TestCase
{
    public static function paginasMetStructuredData(): array
    {
        return [
            'homepage' => ['/', 3],
            'zakelijk' => ['/zakelijk', 2],
            'scriptie' => ['/scriptie-printen', 2],
            'boekje' => ['/boekje-maken', 2],
            'reader' => ['/reader-printen', 2],
            'handleiding' => ['/handleiding-printen', 2],
            'cursusmateriaal' => ['/cursusmateriaal-printen', 2],
        ];
    }

    /** @return string[] de ruwe inhoud van elk ld+json blok */
    private function blokken(string $html): array
    {
        preg_match_all(
            '#<script[^>]*type="application/ld\+json"[^>]*>(.*?)</script>#s',
            $html,
            $matches,
        );

        return $matches[1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paginasMetStructuredData')]
    public function test_pagina_bevat_het_verwachte_aantal_blokken(string $uri, int $verwacht): void
    {
        $blokken = $this->blokken($this->get($uri)->getContent());

        $this->assertCount($verwacht, $blokken, "onverwacht aantal JSON-LD blokken op {$uri}");
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paginasMetStructuredData')]
    public function test_elk_blok_is_geldige_json_met_schema_org_context(string $uri): void
    {
        foreach ($this->blokken($this->get($uri)->getContent()) as $i => $blok) {
            $data = json_decode($blok, true);

            $this->assertNotNull(
                $data,
                "JSON-LD blok #{$i} op {$uri} is geen geldige JSON: ".json_last_error_msg(),
            );
            $this->assertSame('https://schema.org', $data['@context'] ?? null, "blok #{$i} op {$uri}");
            $this->assertArrayHasKey('@type', $data, "blok #{$i} op {$uri}");
        }
    }

    public function test_homepage_beschrijft_de_dienst_het_product_en_de_faq(): void
    {
        $types = array_map(
            fn ($blok) => json_decode($blok, true)['@type'] ?? null,
            $this->blokken($this->get('/')->getContent()),
        );

        $this->assertEqualsCanonicalizing(['PrintingService', 'Product', 'FAQPage'], $types);
    }
}
