<?php

namespace App\Console\Commands;

use App\Models\CreditNote;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Financiële controletelling uit de orderadministratie: unieke betaalde orders,
 * bruto orderbedrag, terugbetalingen en hoeveel van die orders GA4 als aankoop
 * heeft bevestigd. Dit is de bron; GA4 is ondersteunend.
 *
 * Bevat geen klantgegevens, alleen bestelnummers en bedragen.
 */
class OrdersReport extends Command
{
    protected $signature = 'orders:report
        {--from= : Eerste dag (YYYY-MM-DD), standaard 7 dagen geleden}
        {--to= : Laatste dag (YYYY-MM-DD), standaard gisteren}
        {--details : Toon elk bestelnummer}';

    protected $description = 'Unieke betaalde orders, orderbedrag en terugbetalingen per periode (testbetalingen uitgesloten)';

    public function handle(): int
    {
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
        $unconfirmed = $orders->whereNull('analytics_purchase_sent_at')->count();

        $this->info("Orderadministratie {$from->toDateString()} t/m {$to->toDateString()} (orderdatum, {$tz})");
        $this->table(['Maatstaf', 'Waarde'], [
            ['Unieke betaalde orders', $orders->count()],
            ['Bruto orderbedrag incl. btw', $this->euro($gross)],
            ['Terugbetaald in deze periode', $this->euro($refunded).' ('.$refunds->count().'×)'],
            ['Netto na terugbetaling', $this->euro($gross - $refunded)],
            ['Gem. orderwaarde (bruto)', $orders->count() ? $this->euro((int) round($gross / $orders->count())) : '-'],
            ['Waarvan aankoop in GA4 bevestigd', ($orders->count() - $unconfirmed).' van '.$orders->count()],
            ['Testbetalingen (niet meegeteld)', $testOrders],
        ]);

        if ($this->option('details')) {
            $this->table(['Bestelnummer', 'Datum', 'Bedrag', 'Terugbetaald', 'GA4 bevestigd'], $orders->map(fn (Order $o) => [
                $o->order_number,
                $o->created_at->timezone($tz)->format('Y-m-d H:i'),
                $this->euro($o->price_total),
                $o->refunded_amount ? $this->euro($o->refunded_amount) : '',
                $o->analytics_purchase_sent_at ? 'ja' : 'nee',
            ])->all());
        }

        return self::SUCCESS;
    }

    private function euro(int $cents): string
    {
        return '€ '.number_format($cents / 100, 2, ',', '.');
    }
}
