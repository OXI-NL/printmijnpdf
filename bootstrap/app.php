<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 301 van /pagina/ en /index.php naar het canonieke adres
        $middleware->prepend(\App\Http\Middleware\CanonicalUrl::class);

        // Mollie webhook heeft geen CSRF token
        $middleware->validateCsrfTokens(except: [
            'webhook/mollie',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
