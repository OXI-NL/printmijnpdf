<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Legt per order vast of de aankoop werkelijk in GA4 staat, op basis van een
 * export uit GA4 (CSV met transactie-ID en eventueel het aantal aankopen).
 * Dit is de enige bron voor "ontvangen in GA4"; wat de browser meldt, is dat niet.
 *
 * Elke betaalde order (geen testbetaling) uit de opgegeven periode krijgt het
 * aantal keer dat hij in de export staat: 0 = ontbreekt, 2+ = dubbel geteld.
 * De periode moet gelijk zijn aan die van de export.
 */
class ImportGa4Purchases extends Command
{
    protected $signature = 'analytics:import-ga4
        {file : CSV-export uit GA4 met de dimensie Transactie-ID}
        {--from= : Eerste dag van de export (YYYY-MM-DD)}
        {--to= : Laatste dag van de export (YYYY-MM-DD)}
        {--dry-run : Alleen tonen, niets opslaan}';

    protected $description = 'Vergelijk betaalde orders met een GA4-export van transactie-ID\'s';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_readable($path)) {
            $this->error("Bestand niet leesbaar: {$path}");

            return self::FAILURE;
        }
        if (! $this->option('from') || ! $this->option('to')) {
            $this->error('Geef de periode van de export op met --from en --to.');

            return self::FAILURE;
        }

        $counts = self::parse((string) file_get_contents($path));
        if ($counts === null) {
            $this->error('Geen kolom Transactie-ID / Transaction ID gevonden in de export.');

            return self::FAILURE;
        }

        $tz = config('app.timezone');
        $from = Carbon::parse($this->option('from'), $tz)->startOfDay();
        $to = Carbon::parse($this->option('to'), $tz)->endOfDay();

        $orders = Order::whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->where(fn ($q) => $q->whereNull('payment_mode')->orWhere('payment_mode', '!=', 'test'))
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $found = $missing = $double = 0;
        foreach ($orders as $order) {
            $count = $counts[$order->order_number] ?? 0;
            $count === 0 ? $missing++ : ($count > 1 ? $double++ : $found++);

            if (! $this->option('dry-run')) {
                $order->forceFill([
                    'analytics_purchase_ga4_count' => min($count, 65535),
                    'analytics_purchase_ga4_checked_at' => now(),
                ])->save();
            }
        }

        $known = Order::whereIn('order_number', array_keys($counts))->pluck('order_number')->all();
        $inPeriod = $orders->pluck('order_number')->all();
        $unknown = array_diff(array_keys($counts), $inPeriod);

        $this->info(($this->option('dry-run') ? '[proef] ' : '')."GA4-export vergeleken met {$orders->count()} betaalde orders ({$from->toDateString()} t/m {$to->toDateString()})");
        $this->table(['Uitkomst', 'Orders'], [
            ['Precies één keer in GA4', $found],
            ['Meer dan één keer in GA4 (dubbel)', $double],
            ['Ontbreekt in GA4', $missing],
        ]);

        if ($unknown) {
            $this->warn('In de export, maar geen betaalde order in deze periode:');
            foreach ($unknown as $id) {
                $this->line('  '.$id.(in_array($id, $known, true) ? ' (andere periode/status of test)' : ' (onbekend)'));
            }
        }

        return self::SUCCESS;
    }

    /**
     * Transactie-ID → aantal aankopen. Werkt met Nederlandse en Engelse GA4-exports;
     * regels die met # beginnen (GA4-kop) worden overgeslagen. Zonder kolom met een
     * aantal telt elke regel als één aankoop. Null als er geen ID-kolom is.
     *
     * @return array<string,int>|null
     */
    public static function parse(string $csv): ?array
    {
        $idCol = $countCol = null;
        $counts = [];
        $delimiter = substr_count($csv, "\t") > substr_count($csv, ',') ? "\t" : ',';

        foreach (preg_split('/\R/', $csv) as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line, "\xEF\xBB\xBF"), '#')) {
                continue;
            }
            $cells = array_map('trim', str_getcsv(ltrim($line, "\xEF\xBB\xBF"), $delimiter, '"', ''));

            if ($idCol === null) {
                foreach ($cells as $i => $cell) {
                    if (preg_match('/^transacti(on|e)[ -]?id$/i', $cell)) {
                        $idCol = $i;
                    } elseif (preg_match('/^(e-?commerce[- ]?)?(purchases|aankopen)$|^transacti(es|ons)$|^(event count|aantal gebeurtenissen)$/i', $cell)) {
                        // Een aantal, geen bedrag: 'Opbrengst uit aankopen' / 'Purchase revenue' vallen erbuiten
                        $countCol ??= $i;
                    }
                }

                continue;
            }

            $id = $cells[$idCol] ?? '';
            if ($id === '' || $id === '(not set)' || str_starts_with($id, 'Totaal') || str_starts_with($id, 'Grand total')) {
                continue;
            }
            $n = $countCol === null ? 1 : (int) preg_replace('/\D/', '', $cells[$countCol] ?? '0');
            $counts[$id] = ($counts[$id] ?? 0) + $n;
        }

        return $idCol === null ? null : $counts;
    }
}
