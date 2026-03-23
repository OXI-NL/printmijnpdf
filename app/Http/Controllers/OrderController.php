<?php

namespace App\Http\Controllers;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmation;
use App\Mail\OrderShipped;
use App\Mail\PaymentFailed;
use App\Models\Order;
use App\Services\BookletImpositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mollie\Laravel\Facades\Mollie;

class OrderController extends Controller
{
    /**
     * Toon de homepage / bestelformulier
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     * Bereken prijs voor AJAX request - analyseert PDF
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:102400',
        ]);

        try {
            $pdf = $request->file('pdf');
            $content = file_get_contents($pdf->getRealPath());
            
            // Tel paginas
            $pageCount = preg_match_all("/\/Page\W/", $content, $matches);
            if ($pageCount === 0) {
                preg_match_all("/\/Type\s*\/Page[^s]/", $content, $matches);
                $pageCount = count($matches[0]);
            }
            if ($pageCount === 0) $pageCount = 1;

            // Detecteer formaat (standaard A4)
            $format = 'A4';
            if (preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $content, $matches)) {
                $width = floatval($matches[3]) - floatval($matches[1]);
                $height = floatval($matches[4]) - floatval($matches[2]);
                if ($width < $height) {
                    list($width, $height) = [$height, $width];
                }
                if ($width < 500) $format = 'A5';
            }

            return response()->json([
                'success' => true,
                'page_count' => $pageCount,
                'format' => $format,
                'has_bleed' => false,
                'bleed_mm' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error('PDF analyse error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Kon PDF niet analyseren'
            ], 400);
        }
    }

    /**
     * Maak een nieuwe bestelling en start betaling
     */
    public function store(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:51200', // Max 50MB
            'page_count' => 'required|integer|min:1',
            'format' => 'required|in:A4,A5',
            'has_bleed' => 'boolean',
            'bleed_mm' => 'nullable|integer',
            'binding_type' => 'required|in:booklet,loose',
            'print_side' => 'required|in:single,double',
            'delivery_type' => 'required|in:shipping,pickup',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'addition' => 'nullable|string|max:20',
            'postcode' => 'required|string|regex:/^\d{4}\s?[A-Za-z]{2}$/',
            'city' => 'required|string|max:255',
        ]);

        try {
            // Upload PDF
            $pdf = $request->file('pdf');
            $originalName = $pdf->getClientOriginalName();
            $storedName = time() . '_' . uniqid() . '.pdf';
            $path = $pdf->storeAs('pdfs', $storedName, 'local');

            // Bereken prijzen (binding_type en delivery_type bepalen kosten)
            $prices = Order::calculatePrice(
                $request->input('page_count'),
                $request->input('format'),
                $request->input('binding_type'),
                $request->input('delivery_type')
            );

            // Maak order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'pdf_original_name' => $originalName,
                'pdf_stored_name' => $storedName,
                'pdf_path' => $path,
                'page_count' => $request->input('page_count'),
                'format' => $request->input('format'),
                'has_bleed' => $request->boolean('has_bleed'),
                'bleed_mm' => $request->input('bleed_mm'),
                'binding_type' => $request->input('binding_type'),
                'print_side' => $request->input('print_side'),
                'price_startup' => $prices['startup'],
                'price_pages' => $prices['pages'],
                'price_binding' => $prices['binding'],
                'price_shipping' => $prices['shipping'],
                'price_total' => $prices['total'],
                'customer_name' => $request->input('name'),
                'customer_email' => $request->input('email'),
                'customer_phone' => $request->input('phone'),
                'address_street' => $request->input('street'),
                'address_number' => $request->input('number'),
                'address_addition' => $request->input('addition'),
                'address_postcode' => strtoupper(str_replace(' ', '', $request->input('postcode'))),
                'address_city' => $request->input('city'),
                'delivery_type' => $request->input('delivery_type'),
            ]);

            // Maak Mollie betaling
            $payment = Mollie::api()->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($prices['total'] / 100, 2, '.', ''),
                ],
                'description' => "PrintMijnPDF - {$order->order_number}",
                'redirectUrl' => route('order.complete', $order->order_number),
                'webhookUrl' => route('webhook.mollie'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'method' => 'ideal',
            ]);

            // Sla Mollie payment ID op
            $order->update(['mollie_payment_id' => $payment->id]);

            // Redirect naar Mollie checkout
            return response()->json([
                'success' => true,
                'redirect_url' => $payment->getCheckoutUrl(),
            ]);

        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Er ging iets mis. Probeer het opnieuw.',
            ], 500);
        }
    }

    /**
     * Mollie webhook voor betalingsupdates
     */
    public function webhook(Request $request)
    {
        try {
            $paymentId = $request->input('id');
            
            if (!$paymentId) {
                return response('No payment ID', 400);
            }

            $payment = Mollie::api()->payments->get($paymentId);
            $orderId = $payment->metadata->order_id ?? null;

            if (!$orderId) {
                Log::warning("Mollie webhook: No order_id in metadata for payment {$paymentId}");
                return response('OK');
            }

            $order = Order::find($orderId);

            if (!$order) {
                Log::warning("Mollie webhook: Order not found for ID {$orderId}");
                return response('OK');
            }

            // Update order status gebaseerd op Mollie status
            if ($payment->isPaid()) {
                if ($order->status === 'pending') {
                    $order->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    
                    // Maak impositie voor boekjes
                    $this->createBookletImposition($order);
                    
                    // Stuur bevestigingsmail
                    $this->sendConfirmationEmail($order);
                    
                    // Notificeer admin (optioneel)
                    $this->notifyAdmin($order);
                    
                    Log::info("Order {$order->order_number} marked as paid");
                }
            } elseif ($payment->isCanceled() || $payment->isExpired() || $payment->isFailed()) {
                // Gebruik alleen 'cancelled' (database ENUM ondersteunt geen 'expired'/'failed')
                if ($order->status === 'pending') {
                    $order->update(['status' => 'cancelled']);
                    $this->sendPaymentFailedEmail($order);
                    Log::info("Order {$order->order_number} payment failed/cancelled/expired");
                }
            }

            return response('OK');

        } catch (\Exception $e) {
            Log::error('Mollie webhook error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }

    /**
     * Pagina na succesvolle betaling
     * Check direct bij Mollie voor actuele status
     */
    public function complete(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Check direct bij Mollie voor actuele status (webhook kan vertraagd zijn)
        if ($order->mollie_payment_id) {
            try {
                $payment = Mollie::api()->payments->get($order->mollie_payment_id);
                
                if ($payment->isPaid() && $order->status === 'pending') {
                    $order->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    $order->refresh();
                    
                    // Stuur bevestigingsmail
                    $this->sendConfirmationEmail($order);
                    $this->notifyAdmin($order);
                    
                } elseif ($payment->isCanceled() || $payment->isExpired() || $payment->isFailed()) {
                    // Update status als nog pending - gebruik alleen 'cancelled' (database ENUM)
                    if ($order->status === 'pending') {
                        $order->update(['status' => 'cancelled']);
                        $order->refresh();
                    }
                    
                    // Stuur de mislukte betaling email (cache voorkomt dubbele)
                    $this->sendPaymentFailedEmail($order);
                }
            } catch (\Exception $e) {
                Log::error("Error checking Mollie status for {$orderNumber}: " . $e->getMessage());
            }
        }
        
        // Als status al cancelled is, stuur alsnog email (cache voorkomt dubbele)
        if ($order->status === 'cancelled') {
            $this->sendPaymentFailedEmail($order);
        }

        return view('order.complete', compact('order'));
    }

    /**
     * Bekijk order status
     */
    public function status(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return view('order.status', compact('order'));
    }

    /**
     * Markeer order als verzonden en stuur track & trace mail
     * Gebruik: POST /admin/orders/{orderNumber}/ship met track_trace in body
     */
    public function ship(Request $request, string $orderNumber)
    {
        $request->validate([
            'track_trace' => 'required|string|max:50',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($order->status !== 'paid' && $order->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Order kan alleen worden verzonden als deze betaald is.',
            ], 400);
        }

        $order->update([
            'status' => 'shipped',
            'track_trace' => $request->input('track_trace'),
            'shipped_at' => now(),
        ]);

        // Stuur verzendnotificatie email
        $this->sendShippedEmail($order);

        Log::info("Order {$order->order_number} shipped with track & trace: {$order->track_trace}");

        return response()->json([
            'success' => true,
            'message' => 'Order gemarkeerd als verzonden. Klant heeft een email ontvangen.',
        ]);
    }

    /**
     * Stuur bevestigingsmail naar klant (met dubbele-email preventie)
     */
    protected function sendConfirmationEmail(Order $order)
    {
        // Voorkom dubbele emails met cache
        $cacheKey = "confirmation_email_sent_{$order->id}";
        
        if (Cache::has($cacheKey)) {
            Log::info("Confirmation email already sent for order {$order->order_number}, skipping");
            return;
        }
        
        try {
            Mail::to($order->customer_email, $order->customer_name)
                ->send(new OrderConfirmation($order));
            
            // Markeer als verzonden voor 24 uur
            Cache::put($cacheKey, true, now()->addHours(24));
                
            Log::info("Confirmation email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send confirmation email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Stuur verzendnotificatie email naar klant
     */
    protected function sendShippedEmail(Order $order)
    {
        try {
            Mail::to($order->customer_email, $order->customer_name)
                ->send(new OrderShipped($order));
                
            Log::info("Shipped email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send shipped email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Notificeer admin over nieuwe betaalde order (met dubbele-email preventie)
     */
    protected function notifyAdmin(Order $order)
    {
        // Voorkom dubbele admin notificaties met cache
        $cacheKey = "admin_notified_{$order->id}";
        
        if (Cache::has($cacheKey)) {
            Log::info("Admin already notified for order {$order->order_number}, skipping");
            return;
        }
        
        try {
            $adminEmail = config('mail.admin_email', 'info@printmijnpdf.nl');

            Mail::to($adminEmail)
                ->send(new AdminOrderNotification($order));
            
            // Markeer als verzonden voor 24 uur
            Cache::put($cacheKey, true, now()->addHours(24));
            
            Log::info("Admin notified for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to notify admin for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Stuur email bij mislukte betaling (met dubbele-email preventie)
     */
    protected function sendPaymentFailedEmail(Order $order)
    {
        // Voorkom dubbele emails met cache
        $cacheKey = "payment_failed_email_sent_{$order->id}";
        
        if (Cache::has($cacheKey)) {
            Log::info("Payment failed email already sent for order {$order->order_number}, skipping");
            return;
        }
        
        try {
            Mail::to($order->customer_email, $order->customer_name)
                ->send(new PaymentFailed($order));
            
            // Markeer als verzonden voor 24 uur
            Cache::put($cacheKey, true, now()->addHours(24));
                
            Log::info("Payment failed email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send payment failed email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Maak boekje impositie (saddle stitch) voor betaalde orders
     */
    protected function createBookletImposition(Order $order)
    {
        // Alleen voor boekjes
        if ($order->binding_type !== 'booklet') {
            return;
        }

        try {
            $impositionService = new BookletImpositionService();
            $result = $impositionService->createImposition($order);
            
            if ($result['success']) {
                Log::info("Imposition created for order {$order->order_number}: {$result['path']}");
            } else {
                Log::warning("Imposition failed for order {$order->order_number}: {$result['message']}");
            }
        } catch (\Exception $e) {
            Log::error("Imposition error for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Formatteer prijs
     */
    protected function formatPrice(int $cents): string
    {
        return '€ ' . number_format($cents / 100, 2, ',', '.');
    }
}
