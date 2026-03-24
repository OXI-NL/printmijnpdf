<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Mollie\Laravel\Facades\Mollie;
use FPDF;

class MonthlyInvoiceSummaryService
{
    /**
     * Genereer maandelijks overzicht PDF van ALLE betaalde bestellingen
     * (niet alleen gefactureerde — dit is voor je eigen boekhouding)
     *
     * @param int $year  Jaar (bijv. 2026)
     * @param int $month Maand (1-12)
     * @return string PDF binary data
     */
    public static function generate(int $year, int $month): string
    {
        $btwPercentage = config('invoice.btw_percentage', 21);

        // Alle betaalde orders van deze maand (betaald, in productie, verzonden, afgeleverd)
        $orders = Order::whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->orderBy('paid_at')
            ->with('invoice')
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
        $pdf->Cell(0, 10, 'MAANDOVERZICHT BESTELLINGEN - ' . strtoupper($periodLabel), 0, 1, 'L');

        $pdf->SetDrawColor(230, 57, 70);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 282, $pdf->GetY());
        $pdf->Ln(6);

        if ($orders->isEmpty()) {
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 20, 'Geen betaalde bestellingen in deze periode.', 0, 1, 'C');
        } else {
            // === TABEL HEADER ===
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(230, 57, 70);
            $pdf->SetTextColor(255, 255, 255);

            // Kolombreedtes (totaal = 267mm voor landscape A4 met 15mm marges)
            $w = [28, 28, 45, 28, 25, 25, 23, 23, 22, 20];

            $pdf->Cell($w[0], 7, 'Bestelnr', 1, 0, 'L', true);
            $pdf->Cell($w[1], 7, 'Factuurnr', 1, 0, 'L', true);
            $pdf->Cell($w[2], 7, 'Klant', 1, 0, 'L', true);
            $pdf->Cell($w[3], 7, 'Excl. BTW', 1, 0, 'R', true);
            $pdf->Cell($w[4], 7, 'BTW 21%', 1, 0, 'R', true);
            $pdf->Cell($w[5], 7, 'Incl. BTW', 1, 0, 'R', true);
            $pdf->Cell($w[6], 7, 'Mollie fee', 1, 0, 'R', true);
            $pdf->Cell($w[7], 7, 'Netto ontv.', 1, 0, 'R', true);
            $pdf->Cell($w[8], 7, 'Betaald op', 1, 0, 'C', true);
            $pdf->Cell($w[9], 7, 'Status', 1, 1, 'C', true);

            // === TABEL RIJEN ===
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            $totalExcl = 0;
            $totalBtw = 0;
            $totalIncl = 0;
            $totalMollie = 0;
            $totalNet = 0;

            foreach ($orders as $order) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);

                // BTW terugrekenen uit inclusieve prijs
                $inclTotal = $order->price_total;
                $exclTotal = (int) round($inclTotal / (1 + $btwPercentage / 100));
                $btwAmount = $inclTotal - $exclTotal;

                // Mollie fee: uit invoice als beschikbaar, anders proberen op te halen
                $mollieFee = null;
                $netReceived = null;

                if ($order->invoice) {
                    $mollieFee = $order->invoice->mollie_fee;
                    $netReceived = $order->invoice->net_received;
                } elseif ($order->mollie_payment_id) {
                    try {
                        $payment = Mollie::api()->payments->get($order->mollie_payment_id);
                        if (isset($payment->settlementAmount)) {
                            $settlementCents = (int) round(floatval($payment->settlementAmount->value) * 100);
                            $mollieFee = $inclTotal - $settlementCents;
                            $netReceived = $settlementCents;
                        }
                    } catch (\Exception $e) {
                        // Geen probleem, laat leeg
                    }
                }

                $customerName = $order->customer_name;
                if (strlen($customerName) > 25) {
                    $customerName = substr($customerName, 0, 22) . '...';
                }

                $invoiceNumber = $order->invoice ? $order->invoice->invoice_number : '-';

                $statusLabel = match($order->status) {
                    'paid' => 'Betaald',
                    'processing' => 'Productie',
                    'shipped' => 'Verzonden',
                    'delivered' => 'Afgeleverd',
                    default => $order->status,
                };

                $pdf->Cell($w[0], 6, $order->order_number, 1, 0, 'L', $fill);
                $pdf->Cell($w[1], 6, $invoiceNumber, 1, 0, 'L', $fill);
                $pdf->Cell($w[2], 6, $customerName, 1, 0, 'L', $fill);
                $pdf->Cell($w[3], 6, self::formatCents($exclTotal), 1, 0, 'R', $fill);
                $pdf->Cell($w[4], 6, self::formatCents($btwAmount), 1, 0, 'R', $fill);
                $pdf->Cell($w[5], 6, self::formatCents($inclTotal), 1, 0, 'R', $fill);
                $pdf->Cell($w[6], 6, $mollieFee !== null ? self::formatCents($mollieFee) : '-', 1, 0, 'R', $fill);
                $pdf->Cell($w[7], 6, $netReceived !== null ? self::formatCents($netReceived) : '-', 1, 0, 'R', $fill);
                $pdf->Cell($w[8], 6, $order->paid_at ? $order->paid_at->format('d-m') : '-', 1, 0, 'C', $fill);
                $pdf->Cell($w[9], 6, $statusLabel, 1, 1, 'C', $fill);

                $totalExcl += $exclTotal;
                $totalBtw += $btwAmount;
                $totalIncl += $inclTotal;
                $totalMollie += $mollieFee ?? 0;
                $totalNet += $netReceived ?? 0;

                $fill = !$fill;
            }

            // === TOTAALRIJ ===
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(230, 230, 230);

            $countLabel = $orders->count() . ' bestelling' . ($orders->count() !== 1 ? 'en' : '');
            $invoicedCount = $orders->filter(fn($o) => $o->invoice)->count();

            $pdf->Cell($w[0] + $w[1] + $w[2], 8, "TOTAAL ({$countLabel})", 1, 0, 'L', true);
            $pdf->Cell($w[3], 8, self::formatCents($totalExcl), 1, 0, 'R', true);
            $pdf->Cell($w[4], 8, self::formatCents($totalBtw), 1, 0, 'R', true);
            $pdf->Cell($w[5], 8, self::formatCents($totalIncl), 1, 0, 'R', true);
            $pdf->Cell($w[6], 8, self::formatCents($totalMollie), 1, 0, 'R', true);
            $pdf->Cell($w[7], 8, self::formatCents($totalNet), 1, 0, 'R', true);
            $pdf->Cell($w[8] + $w[9], 8, '', 1, 1, 'C', true);

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

            $pdf->Cell($labelW, 7, 'Totaal bestellingen:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($valW, 7, (string) $orders->count(), 0, 1);

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($labelW, 7, 'Waarvan gefactureerd:', 0, 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($valW, 7, (string) $invoicedCount, 0, 1);

            $pdf->Ln(3);

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
        return "Maandoverzicht_bestellingen_{$monthName}_{$year}.pdf";
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
