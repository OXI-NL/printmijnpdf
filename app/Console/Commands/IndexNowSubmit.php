<?php

namespace App\Console\Commands;

use App\Http\Controllers\LandingPageController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

/**
 * Meldt alle publieke pagina's bij IndexNow (Bing, Yandex, Seznam e.a.),
 * zodat nieuwe en gewijzigde pagina's snel in Bing en daarmee in ChatGPT
 * Search komen. Draaien na een deploy die pagina's toevoegt of wijzigt.
 */
class IndexNowSubmit extends Command
{
    protected $signature = 'seo:indexnow {--dry-run : Toon de URL\'s zonder ze te versturen}';

    protected $description = 'Meld alle publieke pagina\'s bij IndexNow (Bing)';

    public function handle(): int
    {
        $key = (string) config('seo.indexnow_key');
        URL::forceRootUrl(rtrim((string) config('seo.site_url'), '/'));
        URL::forceScheme('https');
        $urls = array_merge([url('/')], array_map(fn ($page) => route($page[0]), LandingPageController::PAGES));

        if ($this->option('dry-run')) {
            $this->line(implode("\n", $urls));

            return self::SUCCESS;
        }

        if (! is_file(public_path("{$key}.txt"))) {
            $this->error("Sleutelbestand public/{$key}.txt ontbreekt.");

            return self::FAILURE;
        }

        $response = Http::timeout(15)->post('https://api.indexnow.org/indexnow', [
            'host' => parse_url(url('/'), PHP_URL_HOST),
            'key' => $key,
            'keyLocation' => url("{$key}.txt"),
            'urlList' => $urls,
        ]);

        // 200 = verwerkt, 202 = ontvangen, sleutel wordt nog gecontroleerd
        if (in_array($response->status(), [200, 202], true)) {
            $this->info(count($urls) . " URL's gemeld (HTTP {$response->status()}).");

            return self::SUCCESS;
        }

        $this->error("IndexNow gaf HTTP {$response->status()}: {$response->body()}");
        $this->line('Verstuurd voor host ' . parse_url(url('/'), PHP_URL_HOST) . ', bijvoorbeeld ' . $urls[0]);

        return self::FAILURE;
    }
}
