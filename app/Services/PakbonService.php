<?php

namespace App\Services;

use App\Models\Order;
use App\Pdf\Fpdi;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\PdfReader\PageBoundaries;

class PakbonService
{
    /**
     * Genereer pakbon PDF en retourneer als string (binary data)
     */
    public static function generate(Order $order): string
    {
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(20, 20, 20);

        $thumbLeft = self::thumbnail($pdf, $order);
        self::quantityBadge($pdf, $order->quantity ?? 1, ($thumbLeft ?? 190) - 5);

        // Logo: beeldmerk + woordmerk "Print<Mijn>PDF", zoals in de siteheader
        $logoHeight = 14;
        $pdf->Image(public_path('PMP_beeldmerk.png'), 20, 20, 0, $logoHeight);
        $pdf->SetXY(20 + $logoHeight * 337 / 392 + 3, 20);
        $pdf->SetFont('Arial', 'B', 24);
        foreach ([['Print', [26, 26, 46]], ['Mijn', [214, 52, 71]], ['PDF', [26, 26, 46]]] as [$part, $rgb]) {
            $pdf->SetTextColor(...$rgb);
            $pdf->Cell($pdf->GetStringWidth($part), $logoHeight, $part, 0, 0, 'L');
        }
        $pdf->SetXY(20, 20 + $logoHeight + 2);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'www.printmijnpdf.nl', 0, 1, 'L');
        $pdf->Cell(0, 5, 'info@printmijnpdf.nl', 0, 1, 'L');

        $pdf->Ln(10);

        // Titel
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(0, 0, 0);
        $isPickup = $order->delivery_type === 'pickup';
        $pdf->Cell(0, 12, $isPickup ? 'PAKBON / AFHAALBON' : 'PAKBON / AFLEVERBON', 0, 1, 'L');

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

        if ($isPickup) {
            // Afhalen: geen afleveradres, wel duidelijk dat de klant het komt halen
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetFillColor(230, 57, 70);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 12, 'WORDT AFGEHAALD - NIET VERZENDEN', 0, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(5);

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(0, 8, 'AFHALER', 0, 1, 'L', true);
            $pdf->Ln(3);

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 6, self::text($order->customer_name), 0, 1);
        } else {
            // Afleveradres
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(0, 8, 'AFLEVERADRES', 0, 1, 'L', true);
            $pdf->Ln(3);

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 6, self::text($order->customer_name), 0, 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 6, self::text($order->address_street . ' ' . $order->address_number . ($order->address_addition ? ' ' . $order->address_addition : '')), 0, 1);
            $pdf->Cell(0, 6, self::text($order->address_postcode . ' ' . $order->address_city), 0, 1);
            $pdf->Cell(0, 6, 'Nederland', 0, 1);
        }

        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, self::text('E-mail: ' . $order->customer_email), 0, 1);
        if ($order->customer_phone) {
            $pdf->Cell(0, 5, self::text('Telefoon: ' . $order->customer_phone), 0, 1);
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
        $pdf->Cell(95, 8, 'Omschrijving', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Formaat', 1, 0, 'C', true);
        $pdf->Cell(20, 8, "Pagina's", 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Aantal', 1, 1, 'C', true);

        // Tabel data
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);

        $filename = $order->pdf_original_name;
        if (mb_strlen($filename) > 45) {
            $filename = mb_substr($filename, 0, 42) . '...';
        }

        $quantity = $order->quantity ?? 1;

        $pdf->Cell(95, 8, self::text($filename), 1, 0, 'L');
        $pdf->Cell(35, 8, self::text($order->format_short_label), 1, 0, 'C');
        $pdf->Cell(20, 8, $order->blank_pages > 0 ? "{$order->page_count}+{$order->blank_pages}" : $order->page_count, 1, 0, 'C');
        $pdf->Cell(20, 8, $quantity, 1, 1, 'C');

        if ($order->binding_type === 'booklet') {
            $pdf->Cell(95, 8, 'Geniet gebrocheerd', 1, 0, 'L');
            $pdf->Cell(75, 8, '', 1, 1, 'C');
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
     * Eerste pagina van de klant-PDF rechtsboven, ter herkenning bij het inpakken.
     * Vector-import via FPDI; lukt dat niet, dan blijft de pakbon gewoon zonder.
     * Geeft de linkerrand van de thumbnail terug, of null zonder thumbnail.
     */
    private static function thumbnail(Fpdi $pdf, Order $order): ?float
    {
        $maxW = 45;
        $maxH = 44;
        $right = 190;
        $top = 20;

        try {
            if (!$order->pdf_path || !Storage::disk('local')->exists($order->pdf_path)) {
                return null;
            }

            $pdf->setSourceFile(Storage::disk('local')->path($order->pdf_path));
            // TrimBox: alleen het eindformaat, zonder afloop en snijtekens
            $tpl = $pdf->importPage(1, PageBoundaries::TRIM_BOX);
            $size = $pdf->getTemplateSize($tpl);
            $scale = min($maxW / $size['width'], $maxH / $size['height']);
            $w = $size['width'] * $scale;
            $h = $size['height'] * $scale;
            $x = $right - $w;

            $pdf->useTemplate($tpl, $x, $top, $w, $h);
        } catch (\Throwable $e) {
            return null;
        }

        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x, $top, $w, $h);

        return $x;
    }

    /**
     * Oplage in grote cijfers links van de thumbnail, zodat er bij het inpakken
     * niet te weinig (of te veel) exemplaren in de doos gaan. Schaalt mee met het
     * aantal cijfers: tussen het logo en de thumbnail, boven de titel.
     */
    private static function quantityBadge(Fpdi $pdf, int $quantity, float $right): void
    {
        $left = 90;      // rechts van logo en contactregels
        $baseline = 54;  // net boven de titel "PAKBON / ..."
        $maxSize = 105;  // pt; houdt ruimte vrij voor het label erboven

        $label = $quantity . chr(0xD7); // "10×" in windows-1252
        $pdf->SetFont('Arial', 'B', 100);
        $size = min($maxSize, 100 * ($right - $left) / $pdf->GetStringWidth($label));
        $pdf->SetFont('Arial', 'B', $size);
        $pdf->SetTextColor(230, 57, 70);
        $pdf->Text($right - $pdf->GetStringWidth($label), $baseline, $label);

        // Kapitaalhoogte van Arial is ~0,716 em
        $capTop = $baseline - $size * 0.3528 * 0.716;
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(100, 100, 100);
        $caption = $quantity === 1 ? 'EXEMPLAAR' : 'EXEMPLAREN';
        $pdf->Text($right - $pdf->GetStringWidth($caption), $capTop - 2.5, $caption);
    }

    /**
     * FPDF's kernfonts kennen alleen windows-1252; zet UTF-8 om zodat
     * namen als "Aléxia" niet als "AlÃ©xia" op de pakbon komen.
     */
    private static function text(?string $value): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $value);

        return $converted === false ? (string) $value : $converted;
    }

    /**
     * Retourneer de bestandsnaam voor de pakbon
     */
    public static function filename(Order $order): string
    {
        return 'Pakbon_' . $order->order_number . '_' . $order->quantity_tag . '.pdf';
    }
}
