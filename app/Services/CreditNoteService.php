<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Order;
use Carbon\Carbon;
use FPDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreditNoteService
{
    /**
     * Maak voor elke (nog niet verwerkte) Mollie-terugbetaling een creditnota.
     * De orderstatus blijft ongemoeid: drukken en verzenden gaan gewoon door.
     *
     * @param  object  $payment  Mollie-betaling (Mollie\Api\Resources\Payment)
     * @return CreditNote[] nieuw aangemaakte creditnota's
     */
    public static function syncRefunds(Order $order, object $payment): array
    {
        if (!$payment->hasRefunds()) {
            return [];
        }

        $created = [];
        foreach ($payment->refunds() as $refund) {
            // queued kan Mollie nog annuleren; failed/canceled is nooit teruggestort
            if (!in_array($refund->status, ['pending', 'processing', 'refunded'], true)) {
                continue;
            }
            if (CreditNote::where('mollie_refund_id', $refund->id)->exists()) {
                continue;
            }

            $created[] = self::create($order, $refund);
        }

        return $created;
    }

    private static function create(Order $order, object $refund): CreditNote
    {
        $btwPercentage = config('invoice.btw_percentage', 21);
        $incl = (int) round(floatval($refund->amount->value) * 100);
        $excl = (int) round($incl / (1 + $btwPercentage / 100));
        $date = Carbon::parse($refund->createdAt ?? now())->timezone(config('app.timezone'));

        $creditNote = CreditNote::create([
            'order_id' => $order->id,
            'credit_number' => CreditNote::generateCreditNumber($date),
            'mollie_refund_id' => $refund->id,
            'credit_date' => $date->toDateString(),
            'amount_excl_btw' => $excl,
            'amount_btw' => $incl - $excl,
            'amount_incl_btw' => $incl,
            'btw_percentage' => $btwPercentage,
            'description' => $refund->description ?? null,
        ]);

        $pdfPath = 'credit_notes/' . $creditNote->credit_number . '.pdf';
        Storage::disk('local')->put($pdfPath, self::generatePdf($creditNote, $order));
        $creditNote->update(['pdf_path' => $pdfPath]);

        Log::info("Creditnota {$creditNote->credit_number} aangemaakt voor {$order->order_number} ({$creditNote->formatted_total})");

        return $creditNote;
    }

    /**
     * Creditnota-pdf, in dezelfde opmaak als de factuur maar met negatieve bedragen
     */
    public static function generatePdf(CreditNote $creditNote, Order $order): string
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(20, 15, 20);
        $pdf->SetAutoPageBreak(false);

        // === HEADER ===
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(230, 57, 70);
        $pdf->Cell(90, 10, config('invoice.company_name'), 0, 0, 'L');

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(120, $pdf->GetY());
        $pdf->Cell(70, 3.5, config('invoice.company_address') . ', ' . config('invoice.company_postcode') . ' ' . config('invoice.company_city'), 0, 1, 'R');
        $pdf->SetX(120);
        $pdf->Cell(70, 3.5, config('invoice.company_website') . '  |  ' . config('invoice.company_email'), 0, 1, 'R');
        $pdf->SetX(120);
        $pdf->Cell(70, 3.5, 'KvK: ' . config('invoice.kvk_number') . '  |  BTW-id: ' . config('invoice.btw_id'), 0, 1, 'R');

        $pdf->Ln(4);

        // === TITEL + LIJN ===
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 9, 'CREDITNOTA', 0, 1, 'L');
        $pdf->SetDrawColor(230, 57, 70);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(5);

        // === GEGEVENS + KLANT naast elkaar ===
        $yStart = $pdf->GetY();
        $lh = 5;
        $lw = 32;

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(90, 4, 'CREDITNOTAGEGEVENS', 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->SetTextColor(0, 0, 0);

        $rows = [
            'Creditnotanr:' => $creditNote->credit_number,
            'Datum:' => $creditNote->credit_date->format('d-m-Y'),
            'Bestelnr:' => $order->order_number,
            'Betreft factuur:' => $order->invoice?->invoice_number ?? '-',
        ];
        foreach ($rows as $label => $value) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell($lw, $lh, $label, 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(55, $lh, $value, 0, 1);
        }
        $yEndLeft = $pdf->GetY();

        $pdf->SetY($yStart);
        $pdf->SetX(120);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(70, 4, 'KLANT', 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->SetTextColor(0, 0, 0);

        $address = [
            self::text($order->address_street . ' ' . $order->address_number . ($order->address_addition ? ' ' . $order->address_addition : '')),
            self::text($order->address_postcode . ' ' . $order->address_city),
        ];
        $pdf->SetX(120);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(70, $lh, self::text($order->customer_name), 0, 1);
        $pdf->SetFont('Arial', '', 9);
        foreach ($order->delivery_type === 'pickup' ? [] : $address as $line) {
            $pdf->SetX(120);
            $pdf->Cell(70, $lh, $line, 0, 1);
        }

        $pdf->SetY(max($yEndLeft, $pdf->GetY()) + 6);

        // === REGEL ===
        $rh = 6;
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(230, 57, 70);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(100, $rh, 'Omschrijving', 1, 0, 'L', true);
        $pdf->Cell(30, $rh, 'Bedrag excl.', 1, 0, 'R', true);
        $pdf->Cell(20, $rh, 'BTW %', 1, 0, 'C', true);
        $pdf->Cell(20, $rh, 'BTW', 1, 1, 'R', true);

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $desc = 'Terugbetaling bestelling ' . $order->order_number;
        if ($creditNote->description) {
            $desc .= ' - ' . self::text($creditNote->description);
        }
        if (mb_strlen($desc) > 65) {
            $desc = mb_substr($desc, 0, 62) . '...';
        }
        $pdf->Cell(100, $rh, $desc, 1, 0, 'L');
        $pdf->Cell(30, $rh, self::formatCents(-$creditNote->amount_excl_btw), 1, 0, 'R');
        $pdf->Cell(20, $rh, number_format($creditNote->btw_percentage, 0) . '%', 1, 0, 'C');
        $pdf->Cell(20, $rh, self::formatCents(-$creditNote->amount_btw), 1, 1, 'R');

        $pdf->Ln(2);

        // === TOTALEN ===
        $xTotals = 115;
        $wLabel = 35;
        $wValue = 30;

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel, 6, 'Subtotaal excl. BTW:', 0, 0, 'R');
        $pdf->Cell($wValue, 6, self::formatCents(-$creditNote->amount_excl_btw), 0, 1, 'R');
        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel, 6, 'BTW ' . number_format($creditNote->btw_percentage, 0) . '%:', 0, 0, 'R');
        $pdf->Cell($wValue, 6, self::formatCents(-$creditNote->amount_btw), 0, 1, 'R');

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($xTotals, $pdf->GetY(), $xTotals + $wLabel + $wValue, $pdf->GetY());
        $pdf->Ln(1);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel, 7, 'Totaal incl. BTW:', 0, 0, 'R');
        $pdf->Cell($wValue, 7, self::formatCents(-$creditNote->amount_incl_btw), 0, 1, 'R');

        $pdf->Ln(1);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX($xTotals);
        $pdf->Cell($wLabel + $wValue, 5, 'Teruggestort via Mollie op ' . $creditNote->credit_date->format('d-m-Y'), 0, 1, 'R');

        // === FOOTER ===
        $pdf->SetY(-25);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 3.5, config('invoice.company_name') . ' is een handelsnaam van ' . config('invoice.legal_name') . '  |  KvK: ' . config('invoice.kvk_number') . '  |  BTW-id: ' . config('invoice.btw_id'), 0, 1, 'C');

        return $pdf->Output('S');
    }

    /** FPDF's kernfonts kennen alleen windows-1252 */
    private static function text(?string $value): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $value);

        return $converted === false ? (string) $value : $converted;
    }

    private static function formatCents(int $cents): string
    {
        return chr(128) . ' ' . ($cents < 0 ? '-' : '') . number_format(abs($cents) / 100, 2, ',', '.');
    }
}
