<?php

namespace Tests\Feature;

use Tests\TestCase;

/** printmijnpdf.nl/review staat als QR-code op het kaartje in elk pakket */
class ReviewRedirectTest extends TestCase
{
    public function test_review_stuurt_tijdelijk_door_naar_google(): void
    {
        $this->get('/review')
            ->assertStatus(302)
            ->assertRedirect(config('seo.google_review'))
            ->assertHeader('X-Robots-Tag', 'noindex');
    }

    public function test_review_met_slash_komt_ook_aan(): void
    {
        $this->get('/review/')->assertRedirect(config('seo.google_review'));
    }

    public function test_review_staat_niet_in_de_sitemap(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('/review<', false);
    }
}
