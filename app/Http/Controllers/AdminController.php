<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BookletImpositionService;
use App\Services\PakbonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FPDF;

class AdminController extends Controller
{
    /**
     * Toon alle bestellingen
     */
    public function orders(Request $request)
    {
        $orders = Order::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.orders', compact('orders'));
    }

    /**
     * Download de geüploade PDF (origineel)
     */
    public function downloadPdf(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if (!$order->pdf_path || !Storage::disk('local')->exists($order->pdf_path)) {
            abort(404, 'PDF niet gevonden');
        }

        $filename = $order->order_number . '_' . $order->pdf_original_name;
        
        return Storage::disk('local')->download($order->pdf_path, $filename);
    }

    /**
     * Download de geïmponeerde PDF (drukklaar voor boekje)
     */
    public function downloadImposedPdf(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Check of het een boekje is
        if ($order->binding_type !== 'booklet') {
            return redirect()->back()->with('error', 'Impositie alleen beschikbaar voor boekjes');
        }

        try {
            $impositionService = new BookletImpositionService();

            // Check of impositie bestaat, zo niet: maak aan
            if (!$impositionService->hasImposition($order)) {
                $result = $impositionService->createImposition($order);
                if (!$result['success']) {
                    return redirect()->back()->with('error', 'Impositie kon niet worden aangemaakt: ' . $result['message']);
                }
                $order->refresh();
            }

            $imposedPath = $impositionService->getImposedPath($order);

            if (!$imposedPath || !file_exists($imposedPath)) {
                return redirect()->back()->with('error', 'Geïmponeerde PDF niet gevonden');
            }

            $filename = $order->order_number . '_INSLAG_' . pathinfo($order->pdf_original_name, PATHINFO_FILENAME) . '.pdf';

            return response()->download($imposedPath, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error("Imposition download failed for {$orderNumber}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Impositie mislukt: ' . $e->getMessage());
        }
    }

    /**
     * Genereer/regenereer impositie voor een order
     */
    public function generateImposition(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($order->binding_type !== 'booklet') {
            return redirect()->back()->with('error', 'Impositie alleen beschikbaar voor boekjes');
        }

        $impositionService = new BookletImpositionService();
        
        // Verwijder oude impositie indien aanwezig
        $impositionService->deleteImposition($order);
        
        // Maak nieuwe impositie
        $result = $impositionService->createImposition($order);

        if ($result['success']) {
            return redirect()->back()->with('success', 'Impositie succesvol aangemaakt');
        } else {
            return redirect()->back()->with('error', 'Impositie mislukt: ' . $result['message']);
        }
    }

    /**
     * Genereer pakbon/afleverbon PDF
     */
    public function pakbon(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $pdfData = PakbonService::generate($order);
        $pdfFilename = PakbonService::filename($order);

        return response($pdfData, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFilename . '"',
        ]);
    }

    /**
     * Update order status (bijv. naar shipped)
     */
    public function updateStatus(Request $request, string $orderNumber)
    {
        $request->validate([
            'status' => 'required|in:paid,processing,shipped,delivered,cancelled',
            'track_trace' => 'nullable|string|max:50',
            'pickup' => 'nullable|boolean',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $data = ['status' => $request->input('status')];
        $isPickup = $request->boolean('pickup');

        $shouldSendEmail = false;

        if ($request->input('status') === 'shipped') {
            $data['shipped_at'] = now();
            if (!$isPickup && $request->input('track_trace')) {
                $data['track_trace'] = $request->input('track_trace');
            }
            $shouldSendEmail = true;
        }

        // EERST updaten
        $order->update($data);

        // Refresh om de nieuwe waarden te laden
        $order->refresh();

        // DAN pas email verzenden
        if ($shouldSendEmail) {
            try {
                if ($isPickup) {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderReadyForPickup($order));
                } else {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderShipped($order));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('E-mail mislukt: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Status bijgewerkt, maar email kon niet worden verzonden: ' . $e->getMessage());
            }
        }

        $statusLabel = $isPickup ? 'Klaar voor afhalen' : $request->input('status');
        return redirect()->back()->with('success', 'Status bijgewerkt naar: ' . $statusLabel);
    }

    /**
     * Verwijder een enkele bestelling
     */
    public function destroy(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Verwijder bijbehorende bestanden
        if ($order->pdf_path && Storage::disk('local')->exists($order->pdf_path)) {
            Storage::disk('local')->delete($order->pdf_path);
        }
        if ($order->pdf_imposed_path && Storage::disk('local')->exists($order->pdf_imposed_path)) {
            Storage::disk('local')->delete($order->pdf_imposed_path);
        }

        $order->delete();

        return redirect()->back()->with('success', "Bestelling {$orderNumber} verwijderd.");
    }

    /**
     * Verwijder meerdere bestellingen tegelijk
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'order_numbers' => 'required|array|min:1',
            'order_numbers.*' => 'string',
        ]);

        $orders = Order::whereIn('order_number', $request->input('order_numbers'))->get();

        foreach ($orders as $order) {
            if ($order->pdf_path && Storage::disk('local')->exists($order->pdf_path)) {
                Storage::disk('local')->delete($order->pdf_path);
            }
            if ($order->pdf_imposed_path && Storage::disk('local')->exists($order->pdf_imposed_path)) {
                Storage::disk('local')->delete($order->pdf_imposed_path);
            }
            $order->delete();
        }

        $count = $orders->count();
        return redirect()->back()->with('success', "{$count} bestelling(en) verwijderd.");
    }
}
