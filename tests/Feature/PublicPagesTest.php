<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Rookproef op alle publieke pagina's.
 *
 * Regressie: Laravel 13 introduceerde de Blade-directive @context, waardoor de
 * JSON-LD sleutel "@context" naar een ongesloten if(): compileerde en homepage
 * plus alle landingspagina's een 500 gaven. Boot- en route-checks misten dat.
 */
class PublicPagesTest extends TestCase
{
    public static function publiekeRoutes(): array
    {
        return [
            'homepage' => ['/'],
            'zakelijk' => ['/zakelijk'],
            'scriptie' => ['/scriptie-printen'],
            'boekje' => ['/boekje-maken'],
            'reader' => ['/reader-printen'],
            'handleiding' => ['/handleiding-printen'],
            'cursusmateriaal' => ['/cursusmateriaal-printen'],
            'sitemap' => ['/sitemap.xml'],
            'health' => ['/up'],
            'admin login' => ['/admin/login'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publiekeRoutes')]
    public function test_pagina_laadt_zonder_fout(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    /** Geen onverwerkte Blade-directives of PHP-tags in de uitvoer. */
    #[\PHPUnit\Framework\Attributes\DataProvider('publiekeRoutes')]
    public function test_pagina_lekt_geen_blade_of_php(string $uri): void
    {
        $html = $this->get($uri)->getContent();

        // @context hoort juist wel in de JSON-LD te staan; @@context betekent dat
        // de escape niet is uitgepakt en control-directives horen nooit in de uitvoer.
        $this->assertStringNotContainsString('@@', $html, 'ge-escapete directive niet uitgepakt');
        $this->assertStringNotContainsString('<?php', $html, 'ruwe PHP in de uitvoer');
        $this->assertDoesNotMatchRegularExpression(
            '/@(if|elseif|else|endif|foreach|endforeach|forelse|isset|endisset)\b/',
            $html,
            'onverwerkte Blade control-directive in de uitvoer',
        );
    }

    public function test_homepage_toont_het_bestelformulier(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('csrf-token', false)
            ->assertSee('type="file"', false);
    }

    public function test_onbekende_pagina_geeft_404(): void
    {
        $this->get('/bestaat-echt-niet')->assertNotFound();
    }
}
