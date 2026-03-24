<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'invoice_date',
        'amount_excl_btw',
        'amount_btw',
        'amount_incl_btw',
        'btw_percentage',
        'startup_excl_btw',
        'pages_excl_btw',
        'binding_excl_btw',
        'shipping_excl_btw',
        'mollie_fee',
        'net_received',
        'company_name',
        'company_address',
        'company_postcode',
        'company_city',
        'kvk_number',
        'btw_id',
        'customer_name',
        'customer_email',
        'customer_address',
        'pdf_path',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'btw_percentage' => 'decimal:2',
    ];

    /**
     * Relatie naar de order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Genereer het volgende factuurnummer (PMF-2026-0001)
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = config('invoice.invoice_prefix', 'PMF');
        $year = now()->format('Y');

        // Zoek het hoogste nummer van dit jaar
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Haal het nummer uit het laatste factuurnummer
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    /**
     * Formatteer bedrag voor weergave
     */
    public function getFormattedTotalAttribute(): string
    {
        return '€ ' . number_format($this->amount_incl_btw / 100, 2, ',', '.');
    }

    public function getFormattedExclBtwAttribute(): string
    {
        return '€ ' . number_format($this->amount_excl_btw / 100, 2, ',', '.');
    }

    public function getFormattedBtwAttribute(): string
    {
        return '€ ' . number_format($this->amount_btw / 100, 2, ',', '.');
    }

    public function getFormattedMollieFeeAttribute(): string
    {
        if ($this->mollie_fee === null) return '-';
        return '€ ' . number_format($this->mollie_fee / 100, 2, ',', '.');
    }

    public function getFormattedNetReceivedAttribute(): string
    {
        if ($this->net_received === null) return '-';
        return '€ ' . number_format($this->net_received / 100, 2, ',', '.');
    }
}
