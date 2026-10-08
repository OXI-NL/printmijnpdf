<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Response;

/**
 * llms.txt: samenvatting van de dienst voor AI-assistenten (ChatGPT, Claude,
 * Perplexity). Prijzen komen uit config/pricing, pagina's uit PAGES, zodat
 * dit bestand niet achterloopt op de site.
 */
class LlmsController extends Controller
{
    public function index(): Response
    {
        $euro = fn (int $cents) => '€ ' . number_format($cents / 100, 2, ',', '.');
        $price = fn (string $key) => $euro((int) config("pricing.{$key}"));
        $total = fn (int $pages, string $format, string $binding, string $delivery = 'shipping', int $qty = 1) => $euro(Order::calculatePrice($pages, $format, $binding, $delivery, $qty)['total']);

        $body = view('seo.llms', [
            'price' => $price,
            'total' => $total,
            'pages' => LandingPageController::links(),
        ])->render();

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
