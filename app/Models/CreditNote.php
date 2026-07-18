<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    public const STATUS_ISSUED = 'ISSUED';

    public const SETTLEMENT_APPLIED = 'APPLIED';

    public const SETTLEMENT_TO_REFUND = 'TO_REFUND';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'creator_id',
        'currency_id',
        'credit_note_number',
        'sequence_number',
        'unique_hash',
        'issue_date',
        'reason',
        'status',
        'settlement_status',
        'exchange_rate',
        'sub_total',
        'tax',
        'total',
        'applied_to_balance',
        'refundable_amount',
        'finalized_at',
        'immutable_hash',
        'finalized_snapshot',
    ];

    protected $hidden = [
        'finalized_snapshot',
    ];

    protected $appends = [
        'pdf_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'finalized_at' => 'datetime',
            'exchange_rate' => 'decimal:6',
            'sub_total' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'applied_to_balance' => 'integer',
            'refundable_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** @return HasMany<CreditNoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function getPdfUrlAttribute(): string
    {
        return url('/credit-notes/pdf/'.$this->unique_hash);
    }
}
