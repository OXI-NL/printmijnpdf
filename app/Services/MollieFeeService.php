<?php

namespace App\Services;

use App\Models\CreditNote;
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
     * bijbehorende orders (betalingen) en creditnota's (terugstortingen). Veilig om
     * opnieuw te draaien: alleen afwijkende of ontbrekende waarden worden geschreven.
     *
     * @param  Carbon|null  $since  stop bij transacties van vóór dit moment (null = hele historie)
     * @param  string|null  $orderNumber  alleen deze order (en zijn creditnota's)
     * @param  callable|null  $report  krijgt (string $nummer, int $kostenInCenten, bool $gewijzigd)
     * @return array{matched: int, updated: int, unchanged: int, without_deductions: int}
     */
    public static function sync(?Carbon $since = null, ?string $orderNumber = null, ?callable $report = null): array
    {
        $stats = ['matched' => 0, 'updated' => 0, 'unchanged' => 0, 'without_deductions' => 0];

        $orders = Order::query()
            ->whereNotNull('mollie_payment_id')
            ->when($orderNumber, fn ($q, $number) => $q->where('order_number', $number))
            ->with('invoice')
            ->get()
            ->keyBy('mollie_payment_id');

        $creditNotes = CreditNote::query()
            ->when($orderNumber, fn ($q, $number) => $q->whereHas('order', fn ($o) => $o->where('order_number', $number)))
            ->get()
            ->keyBy('mollie_refund_id');

        if ($orders->isEmpty() && $creditNotes->isEmpty()) {
            return $stats;
        }

        foreach (self::client()->balanceTransactions->iteratorForPrimary(['limit' => 250]) as $transaction) {
            if ($since && Carbon::parse($transaction->createdAt)->lt($since)) {
                break;
            }

            [$pool, $id] = match ($transaction->type ?? null) {
                'payment' => [$orders, self::paymentId($transaction)],
                'refund' => [$creditNotes, self::refundId($transaction)],
                default => [null, null],
            };
            $subject = $id ? $pool->pull($id) : null;
            if (! $subject) {
                continue;
            }

            $fee = abs(self::cents($transaction->deductions->value ?? '0'));
            if ($fee === 0) {
                // Geen ingehouden kosten: niet als "€ 0" vastleggen (dat was precies de
                // oude fout), maar onbekend laten en melden.
                $stats['without_deductions']++;
            } else {
                $changed = $subject instanceof Order
                    ? self::apply($subject, $transaction)
                    : self::applyRefund($subject, $transaction);
                $stats['matched']++;
                $stats[$changed ? 'updated' : 'unchanged']++;
                if ($report) {
                    $report($subject instanceof Order ? $subject->order_number : $subject->credit_number, $fee, $changed);
                }
            }

            if ($orders->isEmpty() && $creditNotes->isEmpty()) {
                break;
            }
        }

        return $stats;
    }

    /** Schrijft de kosten van een terugstorting op de creditnota. Geeft true als er iets veranderde. */
    public static function applyRefund(CreditNote $creditNote, object $transaction): bool
    {
        $fee = abs(self::cents($transaction->deductions->value ?? '0'));
        $values = [
            'mollie_fee' => $fee,
            'mollie_fee_vat' => self::vatPart($fee),
            'mollie_balance_transaction_id' => $transaction->id ?? null,
        ];

        $changed = collect($values)->contains(fn ($value, $key) => $creditNote->{$key} !== $value);
        if ($changed) {
            $creditNote->update($values + ['mollie_fee_synced_at' => now()]);
        }

        return $changed;
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

    /** Het refund-id uit de context van een refund-transactie. */
    private static function refundId(object $transaction): ?string
    {
        $context = $transaction->context ?? null;

        return $context->refundId ?? $context->refund->refundId ?? null;
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
