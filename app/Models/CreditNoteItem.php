<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price' => 'integer',
        'sub_total' => 'integer',
        'tax' => 'integer',
        'total' => 'integer',
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
