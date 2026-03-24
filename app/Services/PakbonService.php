<?php

namespace App\Services;

use App\Models\Order;
use FPDF;

class PakbonService
{
    /**
     * Genereer pakbon PDF en retourneer als string (binary data)
     */
    public static function generate(Order $order): string
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);

        // Logo/Header
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(230, 57, 70);
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
        $pdf->Cell(105, 8, 'Omschrijving', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Formaat', 1, 0, 'C', true);
        $pdf->Cell(20, 8, "Pagina's", 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Aantal', 1, 1, 'C', true);

        // Tabel data
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);

        $filename = $order->pdf_original_name;
        if (strlen($filename) > 50) {
            $filename = substr($filename, 0, 47) . '...';
        }

        $quantity = $order->quantity ?? 1;

        $pdf->Cell(105, 8, $filename, 1, 0, 'L');
        $pdf->Cell(25, 8, $order->format, 1, 0, 'C');
        $pdf->Cell(20, 8, $order->page_count, 1, 0, 'C');
        $pdf->Cell(20, 8, $quantity, 1, 1, 'C');

        if ($order->binding_type === 'booklet') {
            $pdf->Cell(105, 8, 'Geniet gebrocheerd', 1, 0, 'L');
            $pdf->Cell(25, 8, '-', 1, 0, 'C');
            $pdf->Cell(20, 8, '-', 1, 0, 'C');
            $pdf->Cell(20, 8, $quantity, 1, 1, 'C');
        }

        $pdf->Ln(15);

        // Opmerkingen
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(0, 8, 'OPMERKINGEN', 0, 1, 'L', true);
        $pdf->Ln(3);

        $pdf->SetFont('Arial', '', 10);

        if ($order->binding_type === 'booklet') {
            $remarks = "- Geprint in full colour op hoogwaardig papier\n- Dubbelzijdig geprint\n- Professioneel geniet (gebrocheerd)\n- Bij vragen: info@printmijnpdf.nl";
        } else {
            $printSide = ($order->print_side === 'double') ? 'Dubbelzijdig' : 'Enkelzijdig';
            $remarks = "- Geprint in full colour op hoogwaardig papier\n- {$printSide} geprint\n- Losse pagina's (niet geniet)\n- Bij vragen: info@printmijnpdf.nl";
        }
        $pdf->MultiCell(0, 6, $remarks, 0, 'L');

        // Footer
        $pdf->SetY(-40);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, 'Bedankt voor uw bestelling bij PrintMijnPDF.nl!', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Deze pakbon is automatisch gegenereerd op ' . now()->format('d-m-Y H:i'), 0, 1, 'C');

        return $pdf->Output('S');
    }

    /**
     * Retourneer de bestandsnaam voor de pakbon
     */
    public static function filename(Order $order): string
    {
        return 'Pakbon_' . $order->order_number . '.pdf';
    }
}
