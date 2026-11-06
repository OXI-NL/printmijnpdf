<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Legt per order vast of de aankoop werkelijk in GA4 staat, op basis van een
 * export uit GA4 (CSV met Transactie-ID en E-commerce-aankopen).
 * Dit is de enige bron voor "ontvangen in GA4"; wat de browser meldt, is dat niet.
 *
 * Elke betaalde order (geen testbetaling) uit de opgegeven periode krijgt het
 * aantal keer dat hij in de export staat: 0 = ontbreekt, 2+ = dubbel geteld.
 * De periode moet gelijk zijn aan die van de export.
 */
class ImportGa4Purchases extends Command
{
    protected $signature = 'analytics:import-ga4
        {file : CSV-export uit GA4: dimensie Transactie-ID, statistiek E-commerce-aankopen}
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

        try {
            $counts = self::parse((string) file_get_contents($path));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

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
     * Transactie-ID → aantal aankoopevents in GA4.
     *
     * Vereist de kolom Transactie-ID en de statistiek E-commerce-aankopen
     * (Ecommerce purchases). Zonder die telling is niet vast te stellen of een
     * aankoop één of meer keer staat: een geaggregeerde export heeft één regel per
     * transactie-ID, ook bij een dubbel geregistreerde aankoop. Algemene tellingen
     * (Aantal gebeurtenissen, Transacties) gelden niet als aankooptelling.
     * Elk aantal moet een geheel getal zijn. Regels die met # beginnen (GA4-kop),
     * '(not set)' en totaalregels worden overgeslagen; een herhaalde kopregel
     * (meerdere tabellen in één export) wordt opnieuw gelezen.
     *
     * @return array<string,int>
     *
     * @throws InvalidArgumentException met een Nederlandse uitleg; er is dan niets opgeslagen
     */
    public static function parse(string $csv): array
    {
        $idCol = $countCol = null;
        $counts = [];
        $delimiter = substr_count($csv, "\t") > substr_count($csv, ',') ? "\t" : ',';

        foreach (preg_split('/\R/', $csv) as $number => $line) {
            $line = ltrim($line, "\xEF\xBB\xBF");
            if (trim($line) === '' || str_starts_with($line, '#')) {
                continue;
            }
            $cells = array_map('trim', str_getcsv($line, $delimiter, '"', ''));

            $headerId = self::findColumn($cells, '/^transacti(on|e)[ -]?id$/i');
            if ($headerId !== null) {
                $idCol = $headerId;
                $countCol = self::findColumn($cells, '/^e-?commerce[- ]?(purchases|aankopen)$/i');
                if ($countCol === null) {
                    throw new InvalidArgumentException(
                        'De export mist de statistiek E-commerce-aankopen (Ecommerce purchases). '
                        .'Zonder die telling is niet te zien of een aankoop één of meer keer in GA4 staat. '
                        .'Aantal gebeurtenissen, Transacties of opbrengst tellen niet als aankooptelling.'
                    );
                }

                continue;
            }

            if ($idCol === null) {
                throw new InvalidArgumentException('Geen kolom Transactie-ID / Transaction ID gevonden in de export.');
            }

            $id = $cells[$idCol] ?? '';
            if ($id === '' || $id === '(not set)' || preg_match('/^(totaal|total|grand total)\b/i', $id)) {
                continue;
            }

            $raw = $cells[$countCol] ?? '';
            if (! preg_match('/^\d+$/', $raw)) {
                throw new InvalidArgumentException(sprintf(
                    'Regel %d (%s): aantal aankopen "%s" is geen geheel getal. Er is niets opgeslagen.',
                    $number + 1, $id, $raw
                ));
            }
            $counts[$id] = ($counts[$id] ?? 0) + (int) $raw;
        }

        if ($idCol === null) {
            throw new InvalidArgumentException('Geen kolom Transactie-ID / Transaction ID gevonden in de export.');
        }

        return $counts;
    }

    private static function findColumn(array $cells, string $pattern): ?int
    {
        foreach ($cells as $i => $cell) {
            if (preg_match($pattern, $cell)) {
                return $i;
            }
        }

        return null;
    }
}
