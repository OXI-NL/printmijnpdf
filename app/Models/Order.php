<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'mollie_payment_id',
        'status',
        'pdf_original_name',
        'pdf_stored_name',
        'pdf_path',
        'pdf_imposed_path',
        'page_count',
        'format',
        'has_bleed',
        'bleed_mm',
        'binding_type',
        'print_side',
        'quantity',
        'price_startup',
        'price_pages',
        'price_binding',
        'price_shipping',
        'price_total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'address_street',
        'address_number',
        'address_addition',
        'address_postcode',
        'address_city',
        'address_country',
        'delivery_type',
        'track_trace',
        'shipped_at',
        'paid_at',
    ];

    protected $casts = [
        'has_bleed' => 'boolean',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    /**
     * Relatie naar factuur
     */
    public function invoice()
    {
        return $this->hasOne(\App\Models\Invoice::class);
    }

    /**
     * Genereer een uniek ordernummer
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'PMP';
        $date = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Bereken de totaalprijs
     */
    public static function calculatePrice(int $pageCount, string $format, string $bindingType = 'booklet', string $deliveryType = 'shipping', int $quantity = 1): array
    {
        $pricePerPage = $format === 'A5'
            ? config('pricing.per_page_a5', 7)
            : config('pricing.per_page_a4', 10);

        // Eerste exemplaar: volledige startkosten, extra exemplaren: €2,50 per stuk
        $startupFirst = config('pricing.startup', 1000);
        $startupExtra = config('pricing.startup_extra', 250);
        $startup = $startupFirst + (($quantity - 1) * $startupExtra);

        // Pagina- en inbindkosten vermenigvuldigen met aantal exemplaren
        $binding = $bindingType === 'booklet' ? config('pricing.binding', 500) : 0;
        $pages = $pageCount * $pricePerPage * $quantity;
        $bindingTotal = $binding * $quantity;

        // Verzendkosten blijven gelijk ongeacht aantal
        $shipping = $deliveryType === 'pickup' ? 0 : config('pricing.shipping', 675);

        return [
            'startup' => $startup,
            'pages' => $pages,
            'binding' => $bindingTotal,
            'shipping' => $shipping,
            'total' => $startup + $pages + $bindingTotal + $shipping,
        ];
    }

    /**
     * Formatteer prijs voor weergave
     */
    public function getFormattedTotalAttribute(): string
    {
        return '€ ' . number_format($this->price_total / 100, 2, ',', '.');
    }

    /**
     * Volledig verzendadres
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_street . ' ' . $this->address_number;
        if ($this->address_addition) {
            $address .= ' ' . $this->address_addition;
        }
        $address .= "\n" . $this->address_postcode . ' ' . $this->address_city;
        
        return $address;
    }

    /**
     * Is de order betaald?
     */
    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'shipped', 'delivered']);
    }

    /**
     * Kan de order nog geannuleerd worden?
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'paid']);
    }

    /**
     * Status label voor weergave
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Wacht op betaling',
            'paid' => 'Betaald',
            'processing' => 'In productie',
            'shipped' => 'Verzonden',
            'delivered' => 'Afgeleverd',
            'cancelled' => 'Geannuleerd',
            'refunded' => 'Terugbetaald',
            default => $this->status,
        };
    }

    /**
     * Status kleur voor UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'paid' => 'blue',
            'processing' => 'purple',
            'shipped' => 'green',
            'delivered' => 'green',
            'cancelled' => 'red',
            'refunded' => 'gray',
            default => 'gray',
        };
    }
}
