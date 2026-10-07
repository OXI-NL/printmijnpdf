<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        // lastmod volgt de laatste wijziging van de bestanden die de pagina's opbouwen
        $homeModified = $this->lastModified([
            resource_path('views/welcome.blade.php'),
        ]);
        $landingModified = $this->lastModified([
            app_path('Http/Controllers/LandingPageController.php'),
            resource_path('views/landing/page.blade.php'),
            config_path('pricing.php'),
        ]);

        $urls = [[
            'loc' => url('/'),
            'lastmod' => $homeModified,
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ]];

        foreach (LandingPageController::PAGES as [$route]) {
            $urls[] = [
                'loc' => route($route),
                'lastmod' => $landingModified,
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '    <url>' . "\n";
            $xml .= '        <loc>' . $url['loc'] . '</loc>' . "\n";
            $xml .= '        <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '        <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '        <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '    </url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * @param string[] $files
     */
    private function lastModified(array $files): string
    {
        $times = array_filter(array_map(fn ($file) => @filemtime($file), $files));

        return date('Y-m-d', $times ? max($times) : time());
    }
}
