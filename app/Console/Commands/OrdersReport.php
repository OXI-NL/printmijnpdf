<?php

namespace App\Console\Commands;

use App\Models\CreditNote;
use App\Models\Order;
use App\Services\PurchaseTracking;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Financiële controletelling uit de orderadministratie: unieke betaalde orders,
 * bruto orderbedrag en terugbetalingen. Dit is de bron; GA4 is ondersteunend.
 *
 * De meetstatus staat apart en zegt eerlijk wat bekend is: wat de browser meldde
 * (geen ontvangstbewijs), welke orders van vóór de meting zijn (onbekend), en wat
 * in een geïmporteerde GA4-export is teruggevonden (analytics:import-ga4).
 *
 * Bevat geen klantgegevens, alleen bestelnummers en bedragen.
 */
class OrdersReport extends Command
{
    protected $signature = 'orders:report
        {--from= : Eerste dag (YYYY-MM-DD), standaard 7 dagen geleden}
        {--to= : Laatste dag (YYYY-MM-DD), standaard gisteren}
        {--details : Toon elk bestelnummer}
        {--missing : Toon alleen orders zonder bekende meting (browser niet afgehandeld of ontbreekt in GA4)}';

    protected $description = 'Unieke betaalde orders, orderbedrag en terugbetalingen per periode (testbetalingen uitgesloten)';

    public function handle(): int
    {
        if (! PurchaseTracking::ready()) {
            $this->error('De migratie voor de meetstatus is nog niet gedraaid (php artisan migrate --force).');

            return self::FAILURE;
        }

        $tz = config('app.timezone');
        $from = Carbon::parse($this->option('from') ?: now($tz)->subDays(7)->toDateString(), $tz)->startOfDay();
        $to = Carbon::parse($this->option('to') ?: now($tz)->subDay()->toDateString(), $tz)->endOfDay();

        $orders = Order::whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->where(fn ($q) => $q->whereNull('payment_mode')->orWhere('payment_mode', '!=', 'test'))
            ->whereBetween('created_at', [$from, $to])
            ->with('creditNotes')
            ->orderBy('created_at')
            ->get()
            ->unique('order_number');

        $refunds = CreditNote::whereBetween('credit_date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('order', fn ($q) => $q->where(fn ($q) => $q->whereNull('payment_mode')->orWhere('payment_mode', '!=', 'test')))
            ->get();

        $testOrders = Order::where('payment_mode', 'test')->whereBetween('created_at', [$from, $to])->count();
        $gross = $orders->sum('price_total');
        $refunded = $refunds->sum('amount_incl_btw');
        $legacy = $orders->where('analytics_purchase_legacy', true);
        $measured = $orders->where('analytics_purchase_legacy', false);
        $handled = $measured->whereNotNull('analytics_purchase_handled_at')->count();
        $checked = $orders->whereNotNull('analytics_purchase_ga4_count');

        $this->info("Orderadministratie {$from->toDateString()} t/m {$to->toDateString()} (orderdatum, {$tz})");
        $this->table(['Maatstaf', 'Waarde'], [
            ['Unieke betaalde orders', $orders->count()],
            ['Bruto orderbedrag incl. btw', $this->euro($gross)],
            ['Terugbetaald in deze periode', $this->euro($refunded).' ('.$refunds->count().'×)'],
            ['Netto na terugbetaling', $this->euro($gross - $refunded)],
            ['Gem. orderwaarde (bruto)', $orders->count() ? $this->euro((int) round($gross / $orders->count())) : '-'],
            ['Testbetalingen (niet meegeteld)', $testOrders],
        ]);

        $this->info('Meetstatus GA4 (zie docs/analytics-meting.md)');
        $this->table(['Status', 'Orders'], [
            ['Browser: event afgehandeld (geen ontvangstbewijs)', $handled],
            ['Browser: niet afgehandeld', $measured->count() - $handled],
            ['Vóór de nieuwe meting (onbekend, niet opnieuw gestuurd)', $legacy->count()],
            ['GA4-export: precies één keer', $checked->where('analytics_purchase_ga4_count', 1)->count()],
            ['GA4-export: dubbel', $checked->where('analytics_purchase_ga4_count', '>', 1)->count()],
            ['GA4-export: ontbreekt', $checked->where('analytics_purchase_ga4_count', 0)->count()],
            ['Niet tegen een GA4-export gecontroleerd', $orders->count() - $checked->count()],
        ]);

        $rows = $orders;
        if ($this->option('missing')) {
            $rows = $orders->filter(fn (Order $o) => $o->analytics_purchase_ga4_count === 0
                || (! $o->analytics_purchase_legacy && $o->analytics_purchase_handled_at === null && ! $o->analytics_purchase_ga4_count));
        }

        if ($this->option('details') || $this->option('missing')) {
            $this->table(['Bestelnummer', 'Datum', 'Bedrag', 'Terugbetaald', 'Browser', 'GA4-export'], $rows->map(fn (Order $o) => [
                $o->order_number,
                $o->created_at->timezone($tz)->format('Y-m-d H:i'),
                $this->euro($o->price_total),
                $o->refunded_amount ? $this->euro($o->refunded_amount) : '',
                PurchaseTracking::browserLabel($o),
                PurchaseTracking::ga4Label($o),
            ])->values()->all());
        }

        return self::SUCCESS;
    }

    private function euro(int $cents): string
    {
        return '€ '.number_format($cents / 100, 2, ',', '.');
    }
}
