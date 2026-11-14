<?php

namespace Tests\Feature;

use App\Http\Middleware\ContentSecurityPolicy;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    public function test_paginas_krijgen_de_policy_alleen_in_meekijkmodus(): void
    {
        $response = $this->get('/over-ons');

        $response->assertOk();
        $response->assertHeader(ContentSecurityPolicy::HEADER, ContentSecurityPolicy::policy());
        // Afdwingen mag pas na een bewuste keuze: een fout hier breekt de bestelflow
        $response->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_policy_laat_de_bronnen_toe_die_de_site_gebruikt(): void
    {
        $policy = ContentSecurityPolicy::policy();

        foreach ([
            "default-src 'self'",
            'https://www.googletagmanager.com',
            'https://cdnjs.cloudflare.com',
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            "object-src 'none'",
            "base-uri 'self'",
            'report-uri /csp-report',
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $policy);
        }
    }

    public function test_melding_wordt_gelogd_zonder_csrf_token(): void
    {
        Log::spy();

        $body = json_encode(['csp-report' => [
            'document-uri' => 'https://printmijnpdf.nl/',
            'violated-directive' => 'script-src-elem',
            'blocked-uri' => 'https://kwaad.example/x.js',
            'original-policy' => 'wordt niet gelogd',
        ]]);

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $body)
            ->assertNoContent();

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn ($message, $context) => $message === 'CSP-melding'
                && $context['blocked-uri'] === 'https://kwaad.example/x.js'
                && ! array_key_exists('original-policy', $context)
        );
    }

    public function test_onzin_als_melding_wordt_genegeerd(): void
    {
        Log::spy();

        $this->call('POST', '/csp-report', [], [], [], [], 'geen json')->assertNoContent();

        Log::shouldNotHaveReceived('warning');
    }
}
