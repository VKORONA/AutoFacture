<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSetting extends Model
{
    use HasFactory;

    public const MODE_ACCRUAL = 'accrual';
    public const MODE_CASH = 'cash';

    public const PROFILE_UNIVERSAL = 'universal_csv';
    public const PROFILE_FEC_COMPATIBLE = 'fec_compatible';
    public const PROFILE_PENNYLANE = 'pennylane';
    public const PROFILE_EBP = 'ebp';
    public const PROFILE_SAGE = 'sage';
    public const PROFILE_CEGID = 'cegid';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'vat_accounts' => 'array',
            'include_payments' => 'boolean',
            'include_documents' => 'boolean',
            'include_commercial_annexes' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function defaults(int $companyId): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            [
                'vat_accounts' => [
                    '20' => '445712',
                    '10' => '445711',
                    '5.5' => '445710',
                    '2.1' => '445713',
                    '0' => null,
                ],
            ],
        );
    }
}
