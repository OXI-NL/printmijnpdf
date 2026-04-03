<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            [
                'loc' => 'https://printmijnpdf.nl/',
                'lastmod' => '2026-04-03',
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
        ];

        // Landingspagina's
        $landingPages = [
            'scriptie-printen' => '0.9',
            'reader-printen' => '0.9',
            'cursusmateriaal-printen' => '0.8',
            'boekje-maken' => '0.8',
            'handleiding-printen' => '0.8',
            'zakelijk' => '0.8',
        ];

        foreach ($landingPages as $slug => $priority) {
            $urls[] = [
                'loc' => 'https://printmijnpdf.nl/' . $slug,
                'lastmod' => '2026-04-03',
                'changefreq' => 'monthly',
                'priority' => $priority,
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
}
