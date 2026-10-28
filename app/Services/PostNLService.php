<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Verzendlabel via de PostNL Shipping API (shipment/v2_2/label). Het label is
 * meteen voorgemeld bij PostNL; de klant krijgt de Track & Trace pas als de
 * admin de verzending bevestigt.
 */
class PostNLService
{
    private const SANDBOX_URL = 'https://api-sandbox.postnl.nl';
    private const LIVE_URL = 'https://api.postnl.nl';

    public static function isConfigured(): bool
    {
        return filled(config('postnl.api_key'));
    }

    /** Alleen binnen Nederland via de API; buitenland blijft handmatig */
    public static function supports(Order $order): bool
    {
        return $order->delivery_type === 'shipping'
            && in_array(strtoupper($order->address_country ?: 'NL'), ['NL', 'NEDERLAND'], true);
    }

    /** A3 past niet door de brievenbus (max. 38 × 26,5 cm) */
    public static function defaultProduct(Order $order): string
    {
        return $order->format === 'A3' ? 'pakket' : array_key_first(config('postnl.products'));
    }

    public static function labelPath(Order $order): string
    {
        return 'labels/' . $order->order_number . '.pdf';
    }

    public static function deleteLabel(Order $order): void
    {
        if ($order->postnl_label_path && Storage::disk('local')->exists($order->postnl_label_path)) {
            Storage::disk('local')->delete($order->postnl_label_path);
        }
    }

    /**
     * Vraag een label aan, bewaar de PDF en zet barcode en verzendvorm op de order.
     *
     * @throws RuntimeException met een melding die de admin kan lezen
     */
    public function createLabel(Order $order, string $product): string
    {
        $productConfig = config("postnl.products.{$product}")
            ?? throw new RuntimeException("Onbekende verzendvorm: {$product}");

        try {
            $response = Http::baseUrl(config('postnl.sandbox') ? self::SANDBOX_URL : self::LIVE_URL)
                ->withHeaders(['apikey' => config('postnl.api_key')])
                ->acceptJson()
                ->timeout(30)
                ->post('/shipment/v2_2/label?confirm=true', $this->payload($order, $productConfig));
        } catch (ConnectionException $e) {
            throw new RuntimeException('PostNL is niet bereikbaar. Probeer het later opnieuw of vul de code handmatig in.');
        }

        // Vaak een productiesleutel met POSTNL_SANDBOX=true, of andersom
        if ($response->status() === 401) {
            throw new RuntimeException('PostNL accepteert de API-sleutel niet. Controleer POSTNL_API_KEY en POSTNL_SANDBOX.');
        }
        if ($response->failed()) {
            throw new RuntimeException('PostNL weigerde het label (HTTP ' . $response->status() . '): ' . $this->errorMessage($response->json()));
        }

        $shipment = $response->json('ResponseShipments.0');
        if (!empty($shipment['Errors'])) {
            throw new RuntimeException('PostNL weigerde het label: ' . $this->errorMessage($shipment));
        }

        $label = collect($shipment['Labels'] ?? [])->firstWhere('Labeltype', 'Label') ?? ($shipment['Labels'][0] ?? null);
        $pdf = base64_decode($label['Content'] ?? '', true);
        if (empty($shipment['Barcode']) || !$pdf || !str_starts_with($pdf, '%PDF')) {
            throw new RuntimeException('PostNL gaf geen bruikbaar label terug.');
        }

        // Een eerder label van deze order vervalt; PostNL rekent alleen gescande zendingen
        self::deleteLabel($order);
        $path = self::labelPath($order);
        Storage::disk('local')->put($path, $pdf);

        $order->update([
            'postnl_barcode' => $shipment['Barcode'],
            'postnl_product' => $product,
            'postnl_label_path' => $path,
            'postnl_label_created_at' => now(),
        ]);

        return $shipment['Barcode'];
    }

    private function payload(Order $order, array $product): array
    {
        $sender = config('postnl.sender');

        return [
            'Customer' => [
                'CustomerCode' => config('postnl.customer_code'),
                'CustomerNumber' => config('postnl.customer_number'),
                'Address' => [
                    'AddressType' => '02',
                    'CompanyName' => $sender['company_name'],
                    'Street' => $sender['street'],
                    'HouseNr' => $sender['house_number'],
                    'Zipcode' => $sender['postcode'],
                    'City' => $sender['city'],
                    'Countrycode' => 'NL',
                ],
            ],
            'Message' => [
                'MessageID' => (string) $order->id,
                'MessageTimeStamp' => now()->format('d-m-Y H:i:s'),
                'Printertype' => 'GraphicFile|PDF',
            ],
            'Shipments' => [[
                'Addresses' => [array_filter([
                    'AddressType' => '01',
                    'Name' => $order->customer_name,
                    'Street' => $order->address_street,
                    'HouseNr' => $order->address_number,
                    'HouseNrExt' => $order->address_addition,
                    'Zipcode' => strtoupper(str_replace(' ', '', $order->address_postcode)),
                    'City' => $order->address_city,
                    'Countrycode' => 'NL',
                ], fn ($value) => filled($value))],
                // PostNL mailt de klant zelf ook over de bezorging
                'Contacts' => [['ContactType' => '01', 'Email' => $order->customer_email]],
                'Dimension' => ['Weight' => $product['weight']],
                'ProductCodeDelivery' => $product['code'],
                'Reference' => $order->order_number,
            ]],
        ];
    }

    private function errorMessage(?array $body): string
    {
        $errors = $body['Errors'] ?? [];
        if ($errors) {
            return collect($errors)->map(fn ($e) => $e['Description'] ?? $e['Error'] ?? $e['Code'] ?? '')->filter()->implode('; ');
        }

        return $body['message'] ?? $body['fault']['faultstring'] ?? 'onbekende fout';
    }
}
