<?php

namespace App\Services;

use App\Models\Invoice;
use FPDF;

class MonthlyInvoiceSummaryService
{
    /**
     * Genereer maandelijks overzicht PDF
     *
     * @param int $year  Jaar (bijv. 2026)
     * @param int $month Maand (1-12)
     * @return string PDF binary data
     */
    public static function generate(int $year, int $month): string
    {
        $invoices = Invoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->orderBy('invoice_number')
            ->with('order')
            ->get();

        $monthName = self::dutchMonth($month);
        $periodLabel = "{$monthName} {$year}";

        $pdf = new FPDF('L', 'mm', 'A4'); // Landscape voor brede tabel
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 25);

        // === HEADER ===
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(230, 57, 70);
        $pdf->Cell(0, 12, config('invoice.company_name', 'PrintMijnPDF'), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, 'KvK: ' . config('invoice.kvk_number') . '  |  BTW-id: ' . config('invoice.btw_id'), 0, 1, 'L');

        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'MAANDOVERZICHT FACTUREN - ' . strtoupper($periodLabel), 0, 1, 'L');

        $pdf->SetDrawColor(230, 57, 70);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 282, $pdf->GetY());
        $pdf->Ln(6);

        if ($invoices->isEmpty()) {
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 20, 'Geen facturen in deze periode.', 0, 1, 'C');
        } else {
            // === TABEL HEADER ===
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(230, 57, 70);
            $pdf->SetTextColor(255, 255, 255);

            // Kolombreedtes (totaal = 267mm voor landscape A4 met 15mm marges)
            $w = [30, 30, 50, 30, 30, 25, 25, 25, 22];

            $pdf->Cell($w[0], 7, 'Factuurnr', 1, 0, 'L', true);
            $pdf->Cell($w[1], 7, 'Bestelnr', 1, 0, 'L', true);
            $pdf->Cell($w[2], 7, 'Klant', 1, 0, 'L', true);
            $pdf->Cell($w[3], 7, 'Excl. BTW', 1, 0, 'R', true);
            $pdf->Cell($w[4], 7, 'BTW 21%', 1, 0, 'R', true);
            $pdf->Cell($w[5], 7, 'Incl. BTW', 1, 0, 'R', true);
            $pdf->Cell($w[6], 7, 'Mollie fee', 1, 0, 'R', true);
            $pdf->Cell($w[7], 7, 'Netto ontv.', 1, 0, 'R', true);
            $pdf->Cell($w[8], 7, 'Datum', 1, 1, 'C', true);

            // === TABEL RIJEN ===
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            $totalExcl = 0;
            $totalBtw = 0;
            $totalIncl = 0;
            $totalMollie = 0;
            $totalNet = 0;

            foreach ($invoices as $inv) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);

                $customerName = $inv->customer_name;
                if (strlen($customerName) > 28) {
                    $customerName = substr($customerName, 0, 25) . '...';
                }

                $orderNumber = $inv->order ? $inv->order->order_number : '-';

                $pdf->Cell($w[0], 6, $inv->invoice_number, 1, 0, 'L', $fill);
                $pdf->Cell($w[1], 6, $orderNumber, 1, 0, 'L', $fill);
                $pdf->Cell($w[2], 6, $customerName, 1, 0, 'L', $fill);
                $pdf->Cell($w[3], 6, self::formatCents($inv->amount_excl_btw), 1, 0, 'R', $fill);
                $pdf->Cell($w[4], 6, self::formatCents($inv->amount_btw), 1, 0, 'R', $fill);
                $pdf->Cell($w[5], 6, self::formatCents($inv->amount_incl_btw), 1, 0, 'R', $fill);
                $pdf->Cell($w[6], 6, $inv->mollie_fee !== null ? self::formatCents($inv->mollie_fee) : '-', 1, 0, 'R', $fill);
                $pdf->Cell($w[7], 6, $inv->net_received !== null ? self::formatCents($inv->net_received) : '-', 1, 0, 'R', $fill);
                $pdf->Cell($w[8], 6, $inv->invoice_date->format('d-m'), 1, 1, 'C', $fill);

                $totalExcl += $inv->amount_excl_btw;
                $totalBtw += $inv->amount_btw;
                $totalIncl += $inv->amount_incl_btw;
                $totalMollie += $inv->mollie_fee ?? 0;
                $totalNet += $inv->net_received ?? 0;

                $fill = !$fill;
            }

            // === TOTAALRIJ ===
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(230, 230, 230);

            $pdf->Cell($w[0] + $w[1] + $w[2], 8, 'TOTAAL (' . $invoices->count() . ' facturen)', 1, 0, 'L', true);
            $pdf->Cell($w[3], 8, self::formatCents($totalExcl), 1, 0, 'R', true);
            $pdf->Cell($w[4], 8, self::formatCents($totalBtw), 1, 0, 'R', true);
            $pdf->Cell($w[5], 8, self::formatCents($totalIncl), 1, 0, 'R', true);
            $pdf->Cell($w[6], 8, self::formatCents($totalMollie), 1, 0, 'R', true);
            $pdf->Cell($w[7], 8, self::formatCents($totalNet), 1, 0, 'R', true);
            $pdf->Cell($w[8], 8, '', 1, 1, 'C', true);

            // === SAMENVATTING ===
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, 'SAMENVATTING ' . strtoupper($periodLabel), 0, 1, 'L');

            $pdf->SetDrawColor(200, 200, 200);
            $pdf->SetLineWidth(0.3);
            $pdf->Line(15, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(4);

            $pdf->SetFont('Arial', '', 10);
            $labelW = 55;
            $valW = 35;

            $pdf->Cell($labelW, 7, 'Aantal facturen:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($valW, 7, (string) $invoices->count(), 0, 1);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($labelW, 7, 'Omzet excl. BTW:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($valW, 7, self::formatCents($totalExcl), 0, 1);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($labelW, 7, 'Af te dragen BTW (21%):', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($valW, 7, self::formatCents($totalBtw), 0, 1);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($labelW, 7, 'Totaal incl. BTW:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($valW, 7, self::formatCents($totalIncl), 0, 1);

            $pdf->Ln(3);
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->Line(15, $pdf->GetY(), 105, $pdf->GetY());
            $pdf->Ln(3);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($labelW, 7, 'Mollie transactiekosten:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(220, 53, 69);
            $pdf->Cell($valW, 7, '- ' . self::formatCents($totalMollie), 0, 1);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($labelW, 7, 'Netto ontvangen:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(40, 167, 69);
            $pdf->Cell($valW, 7, self::formatCents($totalNet), 0, 1);
        }

        // === FOOTER ===
        $pdf->SetY(-20);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 4, 'Gegenereerd op ' . now()->format('d-m-Y H:i') . ' | Dit document is uitsluitend voor interne administratie', 0, 1, 'C');

        return $pdf->Output('S');
    }

    /**
     * Bestandsnaam voor het overzicht
     */
    public static function filename(int $year, int $month): string
    {
        $monthName = self::dutchMonth($month);
        return "Maandoverzicht_facturen_{$monthName}_{$year}.pdf";
    }

    /**
     * Nederlandse maandnaam
     */
    private static function dutchMonth(int $month): string
    {
        $months = [
            1 => 'januari', 2 => 'februari', 3 => 'maart',
            4 => 'april', 5 => 'mei', 6 => 'juni',
            7 => 'juli', 8 => 'augustus', 9 => 'september',
            10 => 'oktober', 11 => 'november', 12 => 'december',
        ];
        return $months[$month] ?? '';
    }

    /**
     * Formatteer centen naar euro
     */
    private static function formatCents(int $cents): string
    {
        return chr(128) . ' ' . number_format($cents / 100, 2, ',', '.');
    }
}
