<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'company_id',
        'credit_note_id',
        'invoice_item_id',
        'name',
        'description',
        'quantity',
        'price',
        'sub_total',
        'tax',
        'total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'price' => 'integer',
            'sub_total' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
        ];
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
