<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\CreditNoteService;
use Illuminate\Console\Command;
use Mollie\Laravel\Facades\Mollie;

/**
 * Vraagt bij Mollie de terugbetalingen op van betaalde orders en maakt de
 * ontbrekende creditnota's. Voor terugbetalingen van vóór de webhook-
 * verwerking; daarna is het een vangnet dat je veilig opnieuw kunt draaien.
 */
class SyncMollieRefunds extends Command
{
    protected $signature = 'mollie:sync-refunds
        {--order= : Alleen dit bestelnummer (bv. PMP-261028-2627)}
        {--days=120 : Orders betaald in de laatste zoveel dagen}';

    protected $description = "Maak creditnota's voor terugbetalingen die al in Mollie staan";

    public function handle(): int
    {
        $orders = Order::whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->whereNotNull('mollie_payment_id')
            ->when(
                $this->option('order'),
                fn ($q, $number) => $q->where('order_number', $number),
                fn ($q) => $q->where('paid_at', '>=', now()->subDays((int) $this->option('days')))
            )
            ->orderBy('id')
            ->get();

        $created = 0;
        foreach ($orders as $order) {
            try {
                $payment = Mollie::api()->payments->get($order->mollie_payment_id);
                foreach (CreditNoteService::syncRefunds($order, $payment) as $creditNote) {
                    $created++;
                    $this->line("  ✓ {$creditNote->credit_number} voor {$order->order_number}: {$creditNote->formatted_total}");
                }
            } catch (\Exception $e) {
                $this->warn("  ! {$order->order_number}: {$e->getMessage()}");
            }
        }

        $this->info("{$orders->count()} orders gecontroleerd, {$created} creditnota('s) aangemaakt.");

        return self::SUCCESS;
    }
}
