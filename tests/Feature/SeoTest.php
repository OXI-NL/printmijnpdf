<?php

namespace Tests\Feature;

use App\Http\Controllers\LandingPageController;
use Tests\TestCase;

/**
 * Vindbaarheid: elke landingspagina staat in de sitemap, is intern gelinkt
 * en heeft een unieke titel, beschrijving en H1.
 */
class SeoTest extends TestCase
{
    public static function landingspaginas(): array
    {
        return array_map(fn ($page) => [$page[0]], LandingPageController::PAGES);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('landingspaginas')]
    public function test_landingspagina_staat_in_de_sitemap(string $route): void
    {
        $this->get('/sitemap.xml')->assertSee('<loc>' . route($route) . '</loc>', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('landingspaginas')]
    public function test_homepage_linkt_naar_landingspagina(string $route): void
    {
        $this->get('/')->assertSee('href="' . route($route) . '"', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('landingspaginas')]
    public function test_landingspagina_heeft_canonical_naar_zichzelf(string $route): void
    {
        $this->get(route($route))->assertSee('<link rel="canonical" href="' . route($route) . '">', false);
    }

    public function test_titels_beschrijvingen_en_h1_zijn_uniek(): void
    {
        $seen = ['title' => [], 'description' => [], 'h1' => []];

        foreach (array_merge(['/'], array_map(fn ($p) => route($p[0]), LandingPageController::PAGES)) as $url) {
            $html = $this->get($url)->getContent();
            preg_match('#<title>(.*?)</title>#s', $html, $title);
            preg_match('#<meta name="description" content="([^"]*)"#', $html, $description);
            preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $h1);

            foreach (['title' => $title[1] ?? '', 'description' => $description[1] ?? '', 'h1' => strip_tags($h1[1] ?? '')] as $key => $value) {
                $this->assertNotSame('', trim($value), "{$key} ontbreekt op {$url}");
                $this->assertNotContains($value, $seen[$key], "{$key} is dubbel op {$url}");
                $seen[$key][] = $value;
            }
        }
    }

    public function test_homepage_h1_bevat_zoekwoord(): void
    {
        preg_match('#<h1[^>]*>(.*?)</h1>#s', $this->get('/')->getContent(), $h1);

        $this->assertStringContainsStringIgnoringCase('pdf printen', strip_tags($h1[1]));
    }

    public function test_sitemap_lastmod_is_geen_vaste_datum(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('<lastmod>2026-04-03</lastmod>', false);
    }

    public function test_llms_txt_volgt_prijzen_en_paginas(): void
    {
        config(['pricing.per_page_a4' => 17]);

        $response = $this->get('/llms.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('Per pagina A4: € 0,17', false);
        foreach (LandingPageController::PAGES as [$route]) {
            $response->assertSee('(' . route($route) . ')', false);
        }
    }

    public function test_indexnow_sleutelbestand_bestaat(): void
    {
        $key = config('seo.indexnow_key');

        $this->assertFileExists(public_path("{$key}.txt"));
        $this->assertSame($key, file_get_contents(public_path("{$key}.txt")));
    }

    public function test_indexnow_meldt_het_live_domein_ongeacht_app_url(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->artisan('seo:indexnow', ['--dry-run' => true])
            ->expectsOutputToContain('https://printmijnpdf.nl/prijzen')
            ->doesntExpectOutputToContain('localhost')
            ->assertSuccessful();
    }

    public function test_indexnow_verstuurt_urllist_als_lijst(): void
    {
        \Illuminate\Support\Facades\Http::fake(['api.indexnow.org/*' => \Illuminate\Support\Facades\Http::response('', 200)]);

        $this->artisan('seo:indexnow')->assertSuccessful();

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $body['host'] === 'printmijnpdf.nl'
                && array_is_list($body['urlList']);
        });
    }
}
