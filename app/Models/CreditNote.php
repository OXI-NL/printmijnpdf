<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'order_id',
        'credit_number',
        'mollie_refund_id',
        'credit_date',
        'amount_excl_btw',
        'amount_btw',
        'amount_incl_btw',
        'btw_percentage',
        'description',
        'pdf_path',
    ];

    protected $casts = [
        'credit_date' => 'date',
        'btw_percentage' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Volgend creditnotanummer: C-PMP-261028-0001. De datum is die van de
     * creditnota, het volgnummer loopt door over alle creditnota's heen.
     */
    public static function generateCreditNumber(CarbonInterface $date): string
    {
        $last = static::orderByDesc('id')->value('credit_number');
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return sprintf('C-PMP-%s-%04d', $date->format('ymd'), $next);
    }

    public function getFormattedTotalAttribute(): string
    {
        return '€ -' . number_format($this->amount_incl_btw / 100, 2, ',', '.');
    }
}
