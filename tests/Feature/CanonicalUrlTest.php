<?php

namespace Tests\Feature;

use App\Http\Middleware\CanonicalUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    /** De testclient haalt zelf de slash aan het eind weg, dus roep de middleware direct aan */
    private function viaMiddleware(string $uri, string $method = 'GET')
    {
        return (new CanonicalUrl)->handle(Request::create($uri, $method), fn () => response('door'));
    }

    public function test_slash_aan_het_eind_gaat_met_301_naar_de_pagina(): void
    {
        $response = $this->viaMiddleware('/prijzen/');
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(url('/prijzen'), $response->headers->get('Location'));
    }

    public function test_query_blijft_behouden(): void
    {
        $response = $this->viaMiddleware('/prijzen/?utm_source=test');
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(url('/prijzen?utm_source=test'), $response->headers->get('Location'));
    }

    public function test_post_wordt_niet_omgeleid(): void
    {
        $this->assertSame('door', $this->viaMiddleware('/api/order/', 'POST')->getContent());
    }

    public function test_index_php_gaat_naar_home_zonder_lus(): void
    {
        $request = Request::create('http://localhost/index.php', 'GET', [], [], [], [
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
        ]);
        $response = (new CanonicalUrl)->handle($request, fn () => response('door'));
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://localhost/', $response->headers->get('Location'));
    }

    public function test_index_php_met_pad_gaat_naar_dat_pad(): void
    {
        $request = Request::create('http://localhost/index.php/prijzen', 'GET', [], [], [], [
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
        ]);
        $response = (new CanonicalUrl)->handle($request, fn () => response('door'));
        $this->assertSame('http://localhost/prijzen', $response->headers->get('Location'));
    }

    public function test_canonieke_pagina_wordt_niet_omgeleid(): void
    {
        $this->get('/')->assertOk();
        $this->get('/prijzen')->assertOk();
    }
}
