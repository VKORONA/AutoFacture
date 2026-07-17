<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'ISSUED';

    protected $guarded = ['id'];

    protected $hidden = ['finalized_snapshot'];

    protected $casts = [
        'issue_date' => 'date',
        'finalized_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'sub_total' => 'integer',
        'tax' => 'integer',
        'total' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function items()
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
