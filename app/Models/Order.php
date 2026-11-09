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
        'payment_mode',
        'status',
        'pdf_original_name',
        'pdf_stored_name',
        'pdf_path',
        'pdf_imposed_path',
        'page_count',
        'blank_pages',
        'format',
        'trim_width_mm',
        'trim_height_mm',
        'has_bleed',
        'bleed_mm',
        'bleed_added',
        'binding_type',
        'print_side',
        'quantity',
        'promo_code',
        'price_startup',
        'price_pages',
        'price_binding',
        'price_shipping',
        'price_discount',
        'price_total',
        'mollie_fee',
        'mollie_fee_vat',
        'mollie_net_received',
        'mollie_balance_transaction_id',
        'mollie_fee_synced_at',
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
        'postnl_barcode',
        'postnl_product',
        'postnl_label_path',
        'postnl_label_created_at',
        'shipped_at',
        'archived_at',
        'paid_at',
        'paid_processed_at',
        'payment_failed_mailed_at',
        // Attribution
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'msclkid',
        'landing_page',
        'referrer',
    ];

    protected $casts = [
        'has_bleed' => 'boolean',
        'bleed_added' => 'boolean',
        'trim_width_mm' => 'float',
        'trim_height_mm' => 'float',
        'paid_at' => 'datetime',
        'paid_processed_at' => 'datetime',
        'mollie_fee' => 'integer',
        'mollie_fee_vat' => 'integer',
        'mollie_net_received' => 'integer',
        'mollie_fee_synced_at' => 'datetime',
        'payment_failed_mailed_at' => 'datetime',
        'analytics_purchase_claimed_at' => 'datetime',
        'analytics_purchase_handled_at' => 'datetime',
        'analytics_purchase_legacy' => 'boolean',
        'analytics_purchase_ga4_count' => 'integer',
        'analytics_purchase_ga4_checked_at' => 'datetime',
        'shipped_at' => 'datetime',
        'postnl_label_created_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Relatie naar factuur
     */
    public function invoice()
    {
        return $this->hasOne(\App\Models\Invoice::class);
    }

    /**
     * Creditnota's: één per Mollie-terugbetaling. Los van de status, want een
     * terugbetaalde order kan nog steeds gedrukt en verzonden worden.
     */
    public function creditNotes()
    {
        return $this->hasMany(\App\Models\CreditNote::class);
    }

    public function getRefundedAmountAttribute(): int
    {
        return (int) $this->creditNotes->sum('amount_incl_btw');
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
    /**
     * Een geniet boekje bestaat altijd uit een veelvoud van 4 pagina's
     * (elk gevouwen vel = 4 pagina's). Ontbrekende pagina's worden blanco
     * aan het eind toegevoegd en meegerekend.
     */
    public static function billablePages(int $pageCount, string $bindingType): int
    {
        return $bindingType === 'booklet' ? (int) ceil($pageCount / 4) * 4 : $pageCount;
    }

    public static function blankPagesNeeded(int $pageCount, string $bindingType): int
    {
        return self::billablePages($pageCount, $bindingType) - $pageCount;
    }

    /**
     * Aantal pagina's waarvoor betaald is, inclusief blanco pagina's
     */
    public function getBilledPageCountAttribute(): int
    {
        return $this->page_count + (int) $this->blank_pages;
    }

    /**
     * Bv. "10" of "10 + 2 blanco"
     */
    public function getPagesLabelAttribute(): string
    {
        return $this->blank_pages > 0
            ? "{$this->page_count} + {$this->blank_pages} blanco"
            : (string) $this->page_count;
    }

    /**
     * Bv. "A4", of bij een afwijkend formaat de snijmaat: "200 × 200 mm (A4-tarief)"
     */
    public function getFormatLabelAttribute(): string
    {
        return $this->trim_width_mm === null || $this->trim_height_mm === null
            ? $this->format
            : "{$this->trim_size_label} ({$this->format}-tarief)";
    }

    /**
     * Voor de productie: met afloop op snijformaat, zonder afloop iets kleiner snijden
     */
    public function getBleedLabelAttribute(): string
    {
        if ($this->has_bleed && $this->bleed_added) {
            return "met {$this->bleed_mm} mm afloop (door ons aangevuld)";
        }

        return $this->has_bleed
            ? "met {$this->bleed_mm} mm afloop"
            : 'zonder afloop (iets kleiner snijden)';
    }

    /**
     * Korte variant voor smalle kolommen (pakbon, factuur): "A4" of "200×200 mm"
     */
    public function getFormatShortLabelAttribute(): string
    {
        return $this->trim_width_mm === null || $this->trim_height_mm === null
            ? $this->format
            : str_replace(' × ', '×', $this->trim_size_label);
    }

    /**
     * Oplage achteraan bestandsnamen, zodat de drukker het aantal direct ziet: "10x"
     */
    public function getQuantityTagAttribute(): string
    {
        return ($this->quantity ?? 1) . 'x';
    }

    public function getTrimSizeLabelAttribute(): ?string
    {
        if ($this->trim_width_mm === null || $this->trim_height_mm === null) {
            return null;
        }

        $mm = fn (float $v) => str_replace('.', ',', (string) round($v, 1));

        return "{$mm($this->trim_width_mm)} × {$mm($this->trim_height_mm)} mm";
    }

    public static function calculatePrice(int $pageCount, string $format, string $bindingType = 'booklet', string $deliveryType = 'shipping', int $quantity = 1, ?string $promoCode = null): array
    {
        $pageCount = self::billablePages($pageCount, $bindingType);

        $pricePerPage = match ($format) {
            'A5' => config('pricing.per_page_a5', 10),
            'A3' => config('pricing.per_page_a3', 20),
            default => config('pricing.per_page_a4', 15),
        };

        $startup = config('pricing.startup', 1000);

        // Pagina- en inbindkosten vermenigvuldigen met aantal exemplaren
        $pages = $pageCount * $pricePerPage * $quantity;

        // Nieten: eerste boekje €5,00, elk extra boekje €2,50
        if ($bindingType === 'booklet') {
            $bindingFirst = config('pricing.binding', 750);
            $bindingExtra = config('pricing.binding_extra', 250);
            $bindingTotal = $bindingFirst + (($quantity - 1) * $bindingExtra);
        } else {
            $bindingTotal = 0;
        }

        // Verzendkosten blijven gelijk ongeacht aantal
        $shipping = $deliveryType === 'pickup' ? 0 : config('pricing.shipping', 750);

        // Kortingscode toepassen
        $discount = 0;
        if ($promoCode) {
            $promoCodes = config('pricing.promo_codes', []);
            $promo = $promoCodes[$promoCode] ?? null;
            if ($promo && ($promo['active'] ?? false)) {
                $pct = $promo['discount_percent'] / 100;
                $appliesTo = $promo['applies_to'] ?? [];
                if (in_array('pages', $appliesTo)) $discount += (int) round($pages * $pct);
                if (in_array('startup', $appliesTo)) $discount += (int) round($startup * $pct);
                if (in_array('binding', $appliesTo)) $discount += (int) round($bindingTotal * $pct);
            }
        }

        return [
            'startup' => $startup,
            'pages' => $pages,
            'binding' => $bindingTotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $startup + $pages + $bindingTotal + $shipping - $discount,
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
     * Alle Mollie-kosten van deze order: de betaling plus eventuele terugstortingen
     * (die staan op de creditnota's). Null zolang mollie:sync-fees de betaling niet kent.
     */
    public function getTotalMollieFeeAttribute(): ?int
    {
        return $this->mollie_fee === null ? null : $this->mollie_fee + (int) $this->creditNotes->sum('mollie_fee');
    }

    public function getFormattedMollieFeeAttribute(): ?string
    {
        $fee = $this->total_mollie_fee;

        return $fee === null ? null : '€ ' . number_format($fee / 100, 2, ',', '.');
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
     * Tot de herstelde herkomstmeting (9 nov 2026) vulde het bestelformulier een ontbrekende
     * bron in als 'direct' / 'none'. Zo'n order heeft dus géén bewezen bron; die waarde
     * blijft ruw in de database staan, maar telt hier niet als campagnelabel.
     */
    public function hasLegacyDirectDefault(): bool
    {
        return $this->utm_source === 'direct'
            && in_array($this->utm_medium, ['none', null], true)
            && !$this->utm_campaign && !$this->utm_term && !$this->utm_content;
    }

    /**
     * Herkomst met de bewijsbron erbij, voor het beheer. Overdrijft niets:
     * - campagnelabel: er stond een utm_source in de instap-URL (zegt wat de link meldt, niet wie hem plaatste);
     * - verwijzer: de browser gaf een externe verwijzende site door;
     * - klik-id: alleen gclid/fbclid/msclkid, geen bewijs van een betaalde campagne;
     * - onbekend: niets vastgelegd. Dat is níet hetzelfde als bewezen direct verkeer.
     *
     * @return array{basis: string, label: string, notes: list<string>, landing_page: ?string, raw: array<string, string>}
     */
    public function getAttributionAttribute(): array
    {
        $referrerHost = $this->referrer ? parse_url($this->referrer, PHP_URL_HOST) : null;
        $clickIds = array_values(array_filter(['gclid', 'fbclid', 'msclkid'], fn ($key) => (bool) $this->{$key}));
        $legacyDefault = $this->hasLegacyDirectDefault();
        $notes = [];

        if ($this->utm_source && !$legacyDefault) {
            $basis = 'campagnelabel';
            $label = 'Campagnelabel: ' . $this->utm_source . ($this->utm_medium ? ' / ' . $this->utm_medium : '');
            if ($referrerHost) {
                $notes[] = 'Verwijzer: ' . $referrerHost;
            }
        } elseif ($referrerHost) {
            $basis = 'verwijzer';
            $label = 'Verwijzer: ' . $referrerHost;
        } elseif ($clickIds) {
            $basis = 'klik-id';
            $label = 'Alleen klik-ID: ' . implode(', ', $clickIds);
        } else {
            $basis = 'onbekend';
            $label = 'Herkomst onbekend';
        }

        if ($clickIds && $basis !== 'klik-id') {
            $notes[] = 'Klik-ID: ' . implode(', ', $clickIds);
        }
        if ($clickIds) {
            $notes[] = 'Een klik-ID bewijst geen betaalde campagne';
        }
        if ($legacyDefault) {
            $notes[] = "Opgeslagen als 'direct / none': standaardwaarde van het oude formulier, geen bewijs";
        }

        $raw = array_filter([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
            'gclid' => $this->gclid,
            'fbclid' => $this->fbclid,
            'msclkid' => $this->msclkid,
            'landing_page' => $this->landing_page,
            'referrer' => $this->referrer,
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'basis' => $basis,
            'label' => $label,
            'notes' => $notes,
            'landing_page' => $this->landing_page,
            'raw' => $raw,
        ];
    }

    public function getAttributionSummaryAttribute(): string
    {
        return $this->attribution['label'];
    }

    /**
     * Heeft de order een expliciet betaald campagnelabel (utm_medium cpc/ppc/paid)?
     * Een losse klik-ID telt niet: die komt ook mee met gedeelde of opgeslagen links.
     */
    public function isFromPaidAds(): bool
    {
        return !$this->hasLegacyDirectDefault()
            && in_array($this->utm_medium, ['cpc', 'ppc', 'paid'], true);
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
