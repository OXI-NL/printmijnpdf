<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmation;
use App\Mail\OrderShipped;
use App\Mail\PaymentFailed;
use App\Models\Order;
use Illuminate\Http\Request;
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
        return view('welcome', [
            'prices' => [
                'a4' => config('pricing.per_page_a4', 10) / 100,
                'a5' => config('pricing.per_page_a5', 7) / 100,
                'startup' => config('pricing.startup', 1000) / 100,
                'binding' => config('pricing.binding', 500) / 100,
                'shipping' => config('pricing.shipping', 500) / 100,
            ]
        ]);
    }

    /**
     * Bereken prijs voor AJAX request
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'page_count' => 'required|integer|min:1',
            'format' => 'required|in:A4,A5',
        ]);

        $prices = Order::calculatePrice(
            $request->input('page_count'),
            $request->input('format')
        );

        return response()->json([
            'success' => true,
            'prices' => $prices,
            'formatted' => [
                'startup' => $this->formatPrice($prices['startup']),
                'pages' => $this->formatPrice($prices['pages']),
                'binding' => $this->formatPrice($prices['binding']),
                'shipping' => $this->formatPrice($prices['shipping']),
                'total' => $this->formatPrice($prices['total']),
            ]
        ]);
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

            // Bereken prijzen (binding_type bepaalt of er inbindkosten zijn)
            $prices = Order::calculatePrice(
                $request->input('page_count'),
                $request->input('format'),
                $request->input('binding_type')
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
                    
                    // Stuur bevestigingsmail
                    $this->sendConfirmationEmail($order);
                    
                    // Notificeer admin (optioneel)
                    $this->notifyAdmin($order);
                    
                    Log::info("Order {$order->order_number} marked as paid");
                }
            } elseif ($payment->isCanceled()) {
                if ($order->status === 'pending') {
                    $order->update(['status' => 'cancelled']);
                    $this->sendPaymentFailedEmail($order);
                    Log::info("Order {$order->order_number} cancelled");
                }
            } elseif ($payment->isExpired()) {
                if ($order->status === 'pending') {
                    $order->update(['status' => 'expired']);
                    $this->sendPaymentFailedEmail($order);
                    Log::info("Order {$order->order_number} expired");
                }
            } elseif ($payment->isFailed()) {
                if ($order->status === 'pending') {
                    $order->update(['status' => 'failed']);
                    $this->sendPaymentFailedEmail($order);
                    Log::info("Order {$order->order_number} payment failed");
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
     */
    public function complete(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

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
     * Stuur bevestigingsmail naar klant
     */
    protected function sendConfirmationEmail(Order $order)
    {
        try {
            Mail::to($order->customer_email, $order->customer_name)
                ->send(new OrderConfirmation($order));
                
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
     * Notificeer admin over nieuwe betaalde order
     */
    protected function notifyAdmin(Order $order)
    {
        try {
            // Stuur email naar admin
            $adminEmail = config('mail.admin_email', 'info@printmijnpdf.nl');
            
            Mail::raw(
                "Nieuwe bestelling ontvangen!\n\n" .
                "Bestelnummer: {$order->order_number}\n" .
                "Klant: {$order->customer_name}\n" .
                "Email: {$order->customer_email}\n" .
                "Bestand: {$order->pdf_original_name}\n" .
                "Formaat: {$order->format} - {$order->page_count} pagina's\n" .
                "Totaal: {$order->formatted_total}\n\n" .
                "Adres:\n{$order->full_address}",
                function ($message) use ($adminEmail, $order) {
                    $message->to($adminEmail)
                        ->subject("🆕 Nieuwe bestelling: {$order->order_number}");
                }
            );
        } catch (\Exception $e) {
            Log::error("Failed to notify admin for order {$order->order_number}: " . $e->getMessage());
        }
        
        Log::info("New paid order: {$order->order_number} - {$order->formatted_total}");
    }

    /**
     * Stuur email bij mislukte betaling
     */
    protected function sendPaymentFailedEmail(Order $order)
    {
        try {
            Mail::to($order->customer_email, $order->customer_name)
                ->send(new PaymentFailed($order));
                
            Log::info("Payment failed email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send payment failed email for order {$order->order_number}: " . $e->getMessage());
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
