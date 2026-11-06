<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Mollie\Api\MollieApiClient;

/**
 * Haalt per order de Mollie-transactiekosten op uit de Balances API.
 *
 * Mollie zet de kosten van een betaling niet op het payment-object (settlementAmount
 * is het volle bedrag, dus "bedrag − settlementAmount" was altijd 0). De kosten staan
 * in de balance transaction van die betaling: "deductions" is wat Mollie inhield,
 * "resultAmount" wat er netto op de balance kwam.
 */
class MollieFeeService
{
    public static function isConfigured(): bool
    {
        return filled(config('mollie_fees.org_token'));
    }

    /** Client met het organization access token; in tests vervangen door een mock. */
    public static function client(): MollieApiClient
    {
        return app('mollie.org_client');
    }

    /**
     * Loopt de balance transactions af (nieuwste eerst) en zet de kosten op de
     * bijbehorende orders. Veilig om opnieuw te draaien: alleen afwijkende of
     * ontbrekende waarden worden geschreven.
     *
     * @param  Carbon|null  $since  stop bij transacties van vóór dit moment (null = hele historie)
     * @param  string|null  $orderNumber  alleen deze order
     * @param  callable|null  $report  krijgt (Order $order, bool $changed) per gevonden order
     * @return array{matched: int, updated: int, unchanged: int}
     */
    public static function sync(?Carbon $since = null, ?string $orderNumber = null, ?callable $report = null): array
    {
        $stats = ['matched' => 0, 'updated' => 0, 'unchanged' => 0];

        $orders = Order::query()
            ->whereNotNull('mollie_payment_id')
            ->when($orderNumber, fn ($q, $number) => $q->where('order_number', $number))
            ->with('invoice')
            ->get()
            ->keyBy('mollie_payment_id');

        if ($orders->isEmpty()) {
            return $stats;
        }

        foreach (self::client()->balanceTransactions->iteratorForPrimary(['limit' => 250]) as $transaction) {
            if ($since && Carbon::parse($transaction->createdAt)->lt($since)) {
                break;
            }
            if (($transaction->type ?? null) !== 'payment') {
                continue;
            }

            $order = $orders->get(self::paymentId($transaction));
            if (! $order) {
                continue;
            }

            $changed = self::apply($order, $transaction);
            $stats['matched']++;
            $stats[$changed ? 'updated' : 'unchanged']++;
            if ($report) {
                $report($order, $changed);
            }

            $orders->forget($order->mollie_payment_id);
            if ($orders->isEmpty()) {
                break;
            }
        }

        return $stats;
    }

    /** Schrijft de kosten van één balance transaction op de order (en de factuur). Geeft true als er iets veranderde. */
    public static function apply(Order $order, object $transaction): bool
    {
        $fee = abs(self::cents($transaction->deductions->value ?? '0'));
        $values = [
            'mollie_fee' => $fee,
            'mollie_fee_vat' => self::vatPart($fee),
            'mollie_net_received' => self::cents($transaction->resultAmount->value ?? '0'),
            'mollie_balance_transaction_id' => $transaction->id ?? null,
        ];

        $invoice = $order->invoice;
        $orderChanged = collect($values)->contains(fn ($value, $key) => $order->{$key} !== $value);
        $invoiceChanged = $invoice
            && ($invoice->mollie_fee !== $values['mollie_fee'] || $invoice->net_received !== $values['mollie_net_received']);

        if ($orderChanged) {
            $order->update($values + ['mollie_fee_synced_at' => now()]);
        }
        // De factuur bewaart de kosten ook (voor de eigen administratie, niet op de klantfactuur)
        if ($invoiceChanged) {
            $invoice->update(['mollie_fee' => $values['mollie_fee'], 'net_received' => $values['mollie_net_received']]);
        }

        return $orderChanged || $invoiceChanged;
    }

    /**
     * Het btw-deel van de kosten. Zitten de ingehouden kosten inclusief btw (standaard),
     * dan is dit het deel dáárvan; anders de btw die Mollie er via de factuur bovenop rekent.
     */
    public static function vatPart(int $fee): int
    {
        $rate = (int) config('mollie_fees.vat_percentage', 21) / 100;

        return config('mollie_fees.vat_included', true)
            ? $fee - (int) round($fee / (1 + $rate))
            : (int) round($fee * $rate);
    }

    /** Het payment-id uit de context; Mollie documenteert zowel context.paymentId als context.payment.paymentId. */
    private static function paymentId(object $transaction): ?string
    {
        $context = $transaction->context ?? null;

        return $context->paymentId ?? $context->payment->paymentId ?? null;
    }

    /** "-0.29" → -29, zonder float-afrondingsfouten. */
    private static function cents(string $value): int
    {
        $negative = str_starts_with($value, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-+')), 2, '0');
        $cents = (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }
}
