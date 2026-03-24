<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mollie\Laravel\Facades\Mollie;
use FPDF;

class InvoiceService
{
    /**
     * Maak een factuur aan voor een order
     */
    public static function createForOrder(Order $order): Invoice
    {
        // Check of er al een factuur bestaat
        $existing = Invoice::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $btwPercentage = config('invoice.btw_percentage', 21);

        // Prijzen zijn inclusief BTW - reken terug
        // Formule: excl = incl / 1.21, btw = incl - excl
        $inclTotal = $order->price_total;
        $exclTotal = (int) round($inclTotal / (1 + $btwPercentage / 100));
        $btwAmount = $inclTotal - $exclTotal;

        // Individuele regels terugrekenen
        $startupExcl = (int) round($order->price_startup / (1 + $btwPercentage / 100));
        $pagesExcl = (int) round($order->price_pages / (1 + $btwPercentage / 100));
        $bindingExcl = (int) round($order->price_binding / (1 + $btwPercentage / 100));
        $shippingExcl = (int) round($order->price_shipping / (1 + $btwPercentage / 100));

        // Corrigeer afrondingsverschil op de grootste post
        $sumExcl = $startupExcl + $pagesExcl + $bindingExcl + $shippingExcl;
        if ($sumExcl !== $exclTotal) {
            $pagesExcl += ($exclTotal - $sumExcl);
        }

        // Mollie fee ophalen
        $mollieFee = null;
        $netReceived = null;
        if ($order->mollie_payment_id) {
            try {
                $payment = Mollie::api()->payments->get($order->mollie_payment_id);
                $paidAmount = (int) round(floatval($payment->amount->value) * 100);

                // settlementAmount is het bedrag dat Mollie uitbetaalt (na aftrek fees)
                if (isset($payment->settlementAmount)) {
                    $settlementCents = (int) round(floatval($payment->settlementAmount->value) * 100);
                    $mollieFee = $paidAmount - $settlementCents;
                    $netReceived = $settlementCents;
                }
            } catch (\Exception $e) {
                Log::warning("Kon Mollie fee niet ophalen voor order {$order->order_number}: " . $e->getMessage());
            }
        }

        // Klantadres samenstellen
        $customerAddress = $order->address_street . ' ' . $order->address_number;
        if ($order->address_addition) {
            $customerAddress .= ' ' . $order->address_addition;
        }
        $customerAddress .= "\n" . $order->address_postcode . ' ' . $order->address_city;

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'amount_excl_btw' => $exclTotal,
            'amount_btw' => $btwAmount,
            'amount_incl_btw' => $inclTotal,
            'btw_percentage' => $btwPercentage,
            'startup_excl_btw' => $startupExcl,
            'pages_excl_btw' => $pagesExcl,
            'binding_excl_btw' => $bindingExcl,
            'shipping_excl_btw' => $shippingExcl,
            'mollie_fee' => $mollieFee,
            'net_received' => $netReceived,
            'company_name' => config('invoice.company_name'),
            'company_address' => config('invoice.company_address'),
            'company_postcode' => config('invoice.company_postcode'),
            'company_city' => config('invoice.company_city'),
            'kvk_number' => config('invoice.kvk_number'),
            'btw_id' => config('invoice.btw_id'),
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_address' => $customerAddress,
        ]);

        // Genereer PDF en sla op
        $pdfData = self::generatePdf($invoice, $order);
        $pdfPath = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('local')->put($pdfPath, $pdfData);

        $invoice->update(['pdf_path' => $pdfPath]);

        return $invoice;
    }

    /**
     * Genereer factuur PDF
     */
    public static function generatePdf(Invoice $invoice, Order $order): string
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 30);

        // === HEADER: Bedrijfsgegevens ===
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(230, 57, 70);
        $pdf->Cell(0, 15, $invoice->company_name, 0, 1, 'L');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, $invoice->company_address, 0, 1, 'L');
        $pdf->Cell(0, 4, $invoice->company_postcode . ' ' . $invoice->company_city, 0, 1, 'L');
        $pdf->Cell(0, 4, config('invoice.company_website'), 0, 1, 'L');
        $pdf->Cell(0, 4, config('invoice.company_email'), 0, 1, 'L');
        if (config('invoice.company_phone')) {
            $pdf->Cell(0, 4, config('invoice.company_phone'), 0, 1, 'L');
        }
        $pdf->Ln(2);
        $pdf->Cell(0, 4, 'KvK: ' . $invoice->kvk_number . '  |  BTW-id: ' . $invoice->btw_id, 0, 1, 'L');
        if (config('invoice.iban')) {
            $pdf->Cell(0, 4, 'IBAN: ' . config('invoice.iban'), 0, 1, 'L');
        }

        $pdf->Ln(8);

        // === TITEL ===
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 12, 'FACTUUR', 0, 1, 'L');

        // Lijn
        $pdf->SetDrawColor(230, 57, 70);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(8);

        // === FACTUURGEGEVENS + KLANTGEGEVENS naast elkaar ===
        $yStart = $pdf->GetY();

        // Links: factuurgegevens
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(90, 5, 'FACTUURGEGEVENS', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Factuurnr:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(55, 6, $invoice->invoice_number, 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Factuurdatum:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(55, 6, $invoice->invoice_date->format('d-m-Y'), 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Bestelnr:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(55, 6, $order->order_number, 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Besteldatum:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(55, 6, $order->created_at->format('d-m-Y'), 0, 1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, 'Betaalmethode:', 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(55, 6, 'iDEAL (via Mollie)', 0, 1);

        $yEndLeft = $pdf->GetY();

        // Rechts: klantgegevens
        $pdf->SetY($yStart);
        $pdf->SetX(115);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(75, 5, 'FACTUURADRES', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetX(115);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(75, 6, $invoice->customer_name, 0, 1);

        $addressLines = explode("\n", $invoice->customer_address);
        $pdf->SetFont('Arial', '', 10);
        foreach ($addressLines as $line) {
            $pdf->SetX(115);
            $pdf->Cell(75, 6, trim($line), 0, 1);
        }
        $pdf->SetX(115);
        $pdf->Cell(75, 6, 'Nederland', 0, 1);
        $pdf->SetX(115);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(75, 6, $invoice->customer_email, 0, 1);

        $yEndRight = $pdf->GetY();
        $pdf->SetY(max($yEndLeft, $yEndRight) + 10);

        // === FACTUURREGELS ===
        $pdf->SetTextColor(0, 0, 0);

        // Tabel header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 57, 70);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(80, 8, 'Omschrijving', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Aantal', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Prijs excl.', 1, 0, 'R', true);
        $pdf->Cell(20, 8, 'BTW %', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'BTW', 1, 1, 'R', true);

        // Tabel rijen
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(250, 250, 250);

        $quantity = $order->quantity ?? 1;
        $fill = false;

        // Startkosten
        if ($invoice->startup_excl_btw > 0) {
            $startupBtw = (int) round($invoice->startup_excl_btw * $invoice->btw_percentage / 100);
            $pdf->Cell(80, 7, 'Startkosten (instellen drukwerk)', 1, 0, 'L', $fill);
            $pdf->Cell(20, 7, '1', 1, 0, 'C', $fill);
            $pdf->Cell(30, 7, self::formatCents($invoice->startup_excl_btw), 1, 0, 'R', $fill);
            $pdf->Cell(20, 7, number_format($invoice->btw_percentage, 0) . '%', 1, 0, 'C', $fill);
            $pdf->Cell(20, 7, self::formatCents($startupBtw), 1, 1, 'R', $fill);
            $fill = !$fill;
        }

        // Pagina's printen
        if ($invoice->pages_excl_btw > 0) {
            $pagesBtw = (int) round($invoice->pages_excl_btw * $invoice->btw_percentage / 100);
            $desc = "Printen {$order->format} - {$order->page_count} pag.";
            if ($order->print_side === 'double') $desc .= ' (dubbelzijdig)';
            $pdf->Cell(80, 7, $desc, 1, 0, 'L', $fill);
            $pdf->Cell(20, 7, (string) $quantity, 1, 0, 'C', $fill);
            $pdf->Cell(30, 7, self::formatCents($invoice->pages_excl_btw), 1, 0, 'R', $fill);
            $pdf->Cell(20, 7, number_format($invoice->btw_percentage, 0) . '%', 1, 0, 'C', $fill);
            $pdf->Cell(20, 7, self::formatCents($pagesBtw), 1, 1, 'R', $fill);
            $fill = !$fill;
        }

        // Inbinden
        if ($invoice->binding_excl_btw > 0) {
            $bindingBtw = (int) round($invoice->binding_excl_btw * $invoice->btw_percentage / 100);
            $pdf->Cell(80, 7, 'Nieten / brocheren', 1, 0, 'L', $fill);
            $pdf->Cell(20, 7, (string) $quantity, 1, 0, 'C', $fill);
            $pdf->Cell(30, 7, self::formatCents($invoice->binding_excl_btw), 1, 0, 'R', $fill);
            $pdf->Cell(20, 7, number_format($invoice->btw_percentage, 0) . '%', 1, 0, 'C', $fill);
            $pdf->Cell(20, 7, self::formatCents($bindingBtw), 1, 1, 'R', $fill);
            $fill = !$fill;
        }

        // Verzendkosten
        if ($invoice->shipping_excl_btw > 0) {
            $shippingBtw = (int) round($invoice->shipping_excl_btw * $invoice->btw_percentage / 100);
            $pdf->Cell(80, 7, 'Verzendkosten', 1, 0, 'L', $fill);
            $pdf->Cell(20, 7, '1', 1, 0, 'C', $fill);
            $pdf->Cell(30, 7, self::formatCents($invoice->shipping_excl_btw), 1, 0, 'R', $fill);
            $pdf->Cell(20, 7, number_format($invoice->btw_percentage, 0) . '%', 1, 0, 'C', $fill);
            $pdf->Cell(20, 7, self::formatCents($shippingBtw), 1, 1, 'R', $fill);
        }

        $pdf->Ln(3);

        // === TOTALEN ===
        $pdf->SetFont('Arial', '', 10);
        $xTotals = 110;
        $wLabel = 40;
        $wValue = 30;

        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel, 7, 'Subtotaal excl. BTW:', 0, 0, 'R');
        $pdf->Cell($wValue, 7, self::formatCents($invoice->amount_excl_btw), 0, 1, 'R');

        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel, 7, 'BTW ' . number_format($invoice->btw_percentage, 0) . '%:', 0, 0, 'R');
        $pdf->Cell($wValue, 7, self::formatCents($invoice->amount_btw), 0, 1, 'R');

        // Dikke lijn
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($xTotals, $pdf->GetY(), $xTotals + $wLabel + $wValue, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel, 8, 'Totaal incl. BTW:', 0, 0, 'R');
        $pdf->Cell($wValue, 8, self::formatCents($invoice->amount_incl_btw), 0, 1, 'R');

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel + $wValue, 6, 'Betaald via iDEAL op ' . ($order->paid_at ? $order->paid_at->format('d-m-Y') : '-'), 0, 1, 'R');

        // === FOOTER ===
        $pdf->SetY(-40);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 4, $invoice->company_name . '  |  KvK: ' . $invoice->kvk_number . '  |  BTW-id: ' . $invoice->btw_id, 0, 1, 'C');
        if (config('invoice.iban')) {
            $pdf->Cell(0, 4, 'IBAN: ' . config('invoice.iban'), 0, 1, 'C');
        }
        $pdf->Cell(0, 4, 'Alle prijzen zijn inclusief 21% BTW. Betaling is reeds voldaan.', 0, 1, 'C');

        return $pdf->Output('S');
    }

    /**
     * Formatteer centen naar euro string
     */
    private static function formatCents(int $cents): string
    {
        return chr(128) . ' ' . number_format($cents / 100, 2, ',', '.');
    }
}
