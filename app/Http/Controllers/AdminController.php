<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
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
     * Download de geüploade PDF
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
     * Genereer pakbon/afleverbon PDF
     */
    public function pakbon(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Maak PDF met FPDF
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);
        
        // Logo/Header
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(230, 57, 70); // #e63946
        $pdf->Cell(0, 15, 'PrintMijnPDF', 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'www.printmijnpdf.nl', 0, 1, 'L');
        $pdf->Cell(0, 5, 'info@printmijnpdf.nl', 0, 1, 'L');
        
        $pdf->Ln(10);
        
        // Titel
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 12, 'PAKBON / AFLEVERBON', 0, 1, 'L');
        
        // Lijn
        $pdf->SetDrawColor(230, 57, 70);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(8);
        
        // Bestelnummer en datum
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(45, 7, 'Bestelnummer:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, $order->order_number, 0, 1);
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(45, 7, 'Besteldatum:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, $order->created_at->format('d-m-Y H:i'), 0, 1);
        
        if ($order->shipped_at) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(45, 7, 'Verzenddatum:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 7, $order->shipped_at->format('d-m-Y'), 0, 1);
        }
        
        if ($order->track_trace) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(45, 7, 'Track & Trace:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 7, $order->track_trace, 0, 1);
        }
        
        $pdf->Ln(10);
        
        // Afleveradres
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(0, 8, 'AFLEVERADRES', 0, 1, 'L', true);
        $pdf->Ln(3);
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, $order->customer_name, 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 6, $order->address_street . ' ' . $order->address_number . ($order->address_addition ? ' ' . $order->address_addition : ''), 0, 1);
        $pdf->Cell(0, 6, $order->address_postcode . ' ' . $order->address_city, 0, 1);
        $pdf->Cell(0, 6, 'Nederland', 0, 1);
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'E-mail: ' . $order->customer_email, 0, 1);
        if ($order->customer_phone) {
            $pdf->Cell(0, 5, 'Telefoon: ' . $order->customer_phone, 0, 1);
        }
        
        $pdf->Ln(10);
        
        // Inhoud pakket
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(0, 8, 'INHOUD PAKKET', 0, 1, 'L', true);
        $pdf->Ln(3);
        
        // Tabel header
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(230, 57, 70);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
        $pdf->Cell(95, 8, 'Omschrijving', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Formaat', 1, 0, 'C', true);
        $pdf->Cell(25, 8, "Pagina's", 1, 0, 'C', true);
        $pdf->Cell(15, 8, 'Aantal', 1, 1, 'C', true);
        
        // Tabel data
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        
        // Bestandsnaam inkorten als nodig
        $filename = $order->pdf_original_name;
        if (strlen($filename) > 45) {
            $filename = substr($filename, 0, 42) . '...';
        }
        
        $pdf->Cell(10, 8, '1', 1, 0, 'C');
        $pdf->Cell(95, 8, $filename, 1, 0, 'L');
        $pdf->Cell(25, 8, $order->format, 1, 0, 'C');
        $pdf->Cell(25, 8, $order->page_count, 1, 0, 'C');
        $pdf->Cell(15, 8, '1', 1, 1, 'C');
        
        // Extra regel voor afwerking (alleen bij boekje)
        if ($order->binding_type === 'booklet') {
            $pdf->Cell(10, 8, '', 1, 0, 'C');
            $pdf->Cell(95, 8, 'Geniet gebrocheerd', 1, 0, 'L');
            $pdf->Cell(25, 8, '-', 1, 0, 'C');
            $pdf->Cell(25, 8, '-', 1, 0, 'C');
            $pdf->Cell(15, 8, '1', 1, 1, 'C');
        }
        
        $pdf->Ln(15);
        
        // Opmerkingen
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(0, 8, 'OPMERKINGEN', 0, 1, 'L', true);
        $pdf->Ln(3);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Dynamische opmerkingen op basis van binding type
        if ($order->binding_type === 'booklet') {
            $remarks = "- Geprint in full colour op hoogwaardig papier\n- Dubbelzijdig geprint\n- Professioneel geniet (gebrocheerd)\n- Bij vragen: info@printmijnpdf.nl";
        } else {
            $printSide = ($order->print_side === 'double') ? 'Dubbelzijdig' : 'Enkelzijdig';
            $remarks = "- Geprint in full colour op hoogwaardig papier\n- {$printSide} geprint\n- Losse pagina's (niet geniet)\n- Bij vragen: info@printmijnpdf.nl";
        }
        $pdf->MultiCell(0, 6, $remarks, 0, 'L');
        
        // Footer onderaan de pagina
        $pdf->SetY(-40);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(5);
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, 'Bedankt voor uw bestelling bij PrintMijnPDF.nl!', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Deze pakbon is automatisch gegenereerd op ' . now()->format('d-m-Y H:i'), 0, 1, 'C');

        // Output
        $pdfFilename = 'Pakbon_' . $order->order_number . '.pdf';
        
        return response($pdf->Output('S', $pdfFilename), 200, [
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
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        
        $data = ['status' => $request->input('status')];
        
        $shouldSendShippedEmail = false;
        
        if ($request->input('status') === 'shipped') {
            $data['shipped_at'] = now();
            if ($request->input('track_trace')) {
                $data['track_trace'] = $request->input('track_trace');
            }
            $shouldSendShippedEmail = true;
        }

        // EERST updaten
        $order->update($data);
        
        // Refresh om de nieuwe waarden te laden
        $order->refresh();
        
        // DAN pas email verzenden (met de nieuwe track_trace waarde)
        if ($shouldSendShippedEmail) {
            try {
                \Illuminate\Support\Facades\Mail::to($order->customer_email)
                    ->send(new \App\Mail\OrderShipped($order));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Verzendmail mislukt: ' . $e->getMessage());
                return redirect()->back()->with('success', 'Status bijgewerkt, maar email kon niet worden verzonden: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Status bijgewerkt naar: ' . $request->input('status'));
    }
}
