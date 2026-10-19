<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Zet de huidige bedrijfsgegevens (adres, KvK, BTW-id) uit config/invoice.php
 * op bestaande facturen en maakt hun PDF opnieuw. Bedoeld voor facturen die
 * met de oude nepwaarden zijn aangemaakt en nog niet zijn gebruikt.
 */
class RefreshInvoiceCompanyData extends Command
{
    protected $signature = 'invoices:refresh-company-data {--dry-run : Toon welke facturen veranderen zonder iets op te slaan}';

    protected $description = 'Werk bedrijfsgegevens op bestaande facturen bij en maak de PDF opnieuw';

    public function handle(): int
    {
        $fields = [
            'company_name' => config('invoice.company_name'),
            'company_address' => config('invoice.company_address'),
            'company_postcode' => config('invoice.company_postcode'),
            'company_city' => config('invoice.company_city'),
            'kvk_number' => config('invoice.kvk_number'),
            'btw_id' => config('invoice.btw_id'),
        ];

        $changed = 0;

        foreach (Invoice::with('order')->orderBy('id')->get() as $invoice) {
            $invoice->fill($fields);
            if (! $invoice->isDirty()) {
                continue;
            }

            $changed++;
            $this->line("{$invoice->invoice_number}: " . implode(', ', array_keys($invoice->getDirty())));

            if ($this->option('dry-run')) {
                continue;
            }

            if (! $invoice->order) {
                $this->warn("  {$invoice->invoice_number}: geen bestelling gevonden, PDF niet opnieuw gemaakt");
                $invoice->save();
                continue;
            }

            $invoice->save();
            $pdfPath = $invoice->pdf_path ?: 'invoices/' . $invoice->invoice_number . '.pdf';
            Storage::disk('local')->put($pdfPath, InvoiceService::generatePdf($invoice, $invoice->order));
            $invoice->update(['pdf_path' => $pdfPath]);
        }

        $this->info($this->option('dry-run')
            ? "{$changed} factuur/facturen zouden worden bijgewerkt (dry-run)."
            : "{$changed} factuur/facturen bijgewerkt.");

        return self::SUCCESS;
    }
}
