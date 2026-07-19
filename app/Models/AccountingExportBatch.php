<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingExportBatch extends Model
{
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'include_payments' => 'boolean',
            'include_documents' => 'boolean',
            'include_commercial_annexes' => 'boolean',
            'invoice_count' => 'integer',
            'credit_note_count' => 'integer',
            'payment_count' => 'integer',
            'entry_count' => 'integer',
            'total_debit' => 'integer',
            'total_credit' => 'integer',
            'difference' => 'integer',
            'manifest' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
