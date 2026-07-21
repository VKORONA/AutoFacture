<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MicroEntrepreneurSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'enabled' => 'boolean',
        'versement_liberatoire' => 'boolean',
        'acre_enabled' => 'boolean',
        'acre_end_date' => 'date',
        'acre_rate_factor' => 'float',
        'rate_overrides' => 'array',
        'rates_verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function forCompany(int $companyId): self
    {
        $defaults = config('micro-entrepreneur.default_settings', []);

        return self::firstOrCreate(
            ['company_id' => $companyId],
            [
                'enabled' => false,
                'declaration_frequency' => $defaults['declaration_frequency'] ?? 'monthly',
                'cfp_profile' => $defaults['cfp_profile'] ?? 'commercial',
                'versement_liberatoire' => $defaults['versement_liberatoire'] ?? false,
                'acre_enabled' => $defaults['acre_enabled'] ?? false,
                'acre_rate_factor' => $defaults['acre_rate_factor'] ?? 0.5,
            ],
        );
    }
}
