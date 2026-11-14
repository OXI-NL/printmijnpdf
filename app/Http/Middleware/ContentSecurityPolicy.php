<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy in meekijkmodus: de browser blokkeert niets, maar
 * meldt op /csp-report wat deze policy zou tegenhouden. Pas als het log een
 * tijd stil blijft kan de headernaam naar Content-Security-Policy.
 *
 * De server zet daarnaast zelf al een afdwingende CSP met alleen
 * upgrade-insecure-requests en frame-ancestors; die blijft ongemoeid.
 *
 * 'unsafe-inline' is nodig zolang de views inline scripts, onclick-attributen
 * en style-attributen gebruiken. De winst zit in de rest: geen scripts van
 * onbekende domeinen, geen plugins, geen <base>-kaping, geen formulieren die
 * naar buiten posten.
 */
class ContentSecurityPolicy
{
    public const HEADER = 'Content-Security-Policy-Report-Only';

    private const DIRECTIVES = [
        'default-src' => "'self'",
        // gtag.js (GA4) en pdf.js voor de voorvertoning
        'script-src' => "'self' 'unsafe-inline' https://www.googletagmanager.com https://cdnjs.cloudflare.com",
        'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
        'font-src' => "'self' https://fonts.gstatic.com",
        // blob:/data: voor de PDF-voorvertoning en miniaturen
        'img-src' => "'self' data: blob: https://www.googletagmanager.com https://*.google-analytics.com",
        'connect-src' => "'self' https://www.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com https://cdnjs.cloudflare.com",
        // pdf.js start zijn worker via een blob
        'worker-src' => "'self' blob: https://cdnjs.cloudflare.com",
        // PostNL-label in de admin
        'frame-src' => "'self' blob: data:",
        'object-src' => "'none'",
        'base-uri' => "'self'",
        'form-action' => "'self'",
        'report-uri' => '/csp-report',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(self::HEADER, self::policy());

        return $response;
    }

    public static function policy(): string
    {
        $parts = [];
        foreach (self::DIRECTIVES as $name => $value) {
            $parts[] = $name . ' ' . $value;
        }

        return implode('; ', $parts);
    }
}
