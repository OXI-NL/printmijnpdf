<?php

namespace App\Console\Commands;

use App\Models\CreditNote;
use App\Models\Order;
use App\Services\MollieFeeService;
use Illuminate\Console\Command;

/**
 * Haalt de Mollie-transactiekosten op uit de Balances API en zet ze per order.
 * Draait dagelijks voor nieuwe betalingen; met --all met terugwerkende kracht.
 * Veilig om opnieuw te draaien: ongewijzigde orders blijven ongemoeid.
 */
class SyncMollieFees extends Command
{
    protected $signature = 'mollie:sync-fees
        {--order= : Alleen dit bestelnummer (bv. PMP-261028-2627)}
        {--days=14 : Balance transactions van de laatste zoveel dagen}
        {--all : De hele historie (met terugwerkende kracht)}';

    protected $description = 'Zet de Mollie-transactiekosten per order uit de Balances API';

    public function handle(): int
    {
        if (! MollieFeeService::isConfigured()) {
            $this->error('MOLLIE_ORG_TOKEN ontbreekt: maak in Mollie een organization access token met balances.read en zet die in .env.');

            return self::FAILURE;
        }

        $since = ($this->option('all') || $this->option('order'))
            ? null
            : now()->subDays((int) $this->option('days'));

        try {
            $stats = MollieFeeService::sync($since, $this->option('order'), function (string $number, int $fee, bool $changed) {
                if ($changed) {
                    $this->line("  ✓ {$number}: € ".number_format($fee / 100, 2, ',', '.'));
                }
            });
        } catch (\Exception $e) {
            $this->error('Mollie Balances API: '.$e->getMessage());

            return self::FAILURE;
        }

        $missing = Order::whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
            ->whereNotNull('mollie_payment_id')
            ->whereNull('mollie_fee')
            ->count()
            + CreditNote::whereNull('mollie_fee')->count();

        $this->info("{$stats['matched']} betalingen/terugstortingen gevonden in Mollie, {$stats['updated']} bijgewerkt, {$stats['unchanged']} al goed.");
        if ($stats['without_deductions'] > 0) {
            $this->warn("{$stats['without_deductions']} transactie(s) zonder ingehouden kosten op de balance transaction; die blijven leeg in plaats van € 0.");
        }
        if ($missing > 0) {
            $this->warn("{$missing} betaalde order(s)/creditnota('s) nog zonder kosten (nog niet op de balance, of buiten de periode: probeer --all).");
        }

        return self::SUCCESS;
    }
}
