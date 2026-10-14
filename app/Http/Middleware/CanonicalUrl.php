<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eén adres per pagina: /prijzen/ en /index.php/prijzen gaan met een 301 naar
 * /prijzen, zodat zoekmachines geen dubbele pagina's zien.
 */
class CanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $uri = $request->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $query = parse_url($uri, PHP_URL_QUERY);

        $canonical = preg_replace('#^/index\.php(?=/|$)#', '', $path);
        $canonical = $canonical === '' ? '/' : $canonical;
        if ($canonical !== '/') {
            $canonical = rtrim($canonical, '/') ?: '/';
        }

        if ($canonical !== $path) {
            // Zelf opbouwen: via /index.php zou url() die basis weer meenemen (lus)
            $target = $request->getSchemeAndHttpHost() . $canonical . ($query !== null ? '?' . $query : '');

            return redirect()->away($target, 301);
        }

        return $next($request);
    }
}
