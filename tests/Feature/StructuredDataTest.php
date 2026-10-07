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
            // Service + BreadcrumbList + FAQPage
            'zakelijk' => ['/zakelijk', 3],
            'scriptie' => ['/scriptie-printen', 3],
            'boekje' => ['/boekje-maken', 3],
            'reader' => ['/reader-printen', 3],
            'handleiding' => ['/handleiding-printen', 3],
            'cursusmateriaal' => ['/cursusmateriaal-printen', 3],
            'pdf laten printen' => ['/pdf-laten-printen', 3],
            'boekje printen' => ['/boekje-printen', 3],
            // + HowTo
            'pdf naar boekje' => ['/pdf-naar-boekje', 4],
        ];
    }

    /** Alleen de URI's, voor tests die het verwachte aantal niet nodig hebben. */
    public static function paginaUris(): array
    {
        return array_map(
            fn (array $case) => [$case[0]],
            self::paginasMetStructuredData(),
        );
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

    #[\PHPUnit\Framework\Attributes\DataProvider('paginaUris')]
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

    public function test_landingspagina_heeft_breadcrumb_naar_home(): void
    {
        $blokken = array_map(fn ($b) => json_decode($b, true), $this->blokken($this->get('/pdf-naar-boekje')->getContent()));
        $breadcrumb = collect($blokken)->firstWhere('@type', 'BreadcrumbList');

        $this->assertSame(url('/'), $breadcrumb['itemListElement'][0]['item']);
        $this->assertSame(route('landing.pdf-naar-boekje'), $breadcrumb['itemListElement'][1]['item']);
    }

    public function test_apostrof_in_faq_staat_niet_als_html_entity_in_json(): void
    {
        $html = $this->get('/boekje-printen')->getContent();

        $this->assertStringNotContainsString('&#039;', implode('', $this->blokken($html)));
        $this->assertStringContainsString("pagina's", implode('', $this->blokken($html)));
    }
}
