<?php

namespace Crater\Models;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Crater\Traits\HasCustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateItem extends Model
{
    use HasCustomFieldsTrait;
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'price' => 'integer',
        'total' => 'integer',
        'discount' => 'float',
        'quantity' => 'float',
        'discount_val' => 'integer',
        'tax' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $estimateItem): void {
            if ($estimateItem->item_id) {
                $catalogType = Item::query()
                    ->whereKey($estimateItem->item_id)
                    ->where('company_id', $estimateItem->company_id)
                    ->value('business_activity_type');

                if ($catalogType) {
                    $estimateItem->business_activity_type = $catalogType instanceof BusinessActivityType
                        ? $catalogType->value
                        : (string) $catalogType;
                }
            }

            $estimateItem->business_activity_type = BusinessActivityType::tryFrom(
                (string) $estimateItem->business_activity_type
            )?->value ?? BusinessActivityType::SERVICE_BIC->value;
        });
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EstimateLinePhoto::class, 'line_uuid', 'line_uuid')
            ->where('estimate_id', $this->estimate_id)
            ->orderBy('sort_order');
    }

    public function scopeWhereCompany($query, $company_id)
    {
        $query->where('company_id', $company_id);
    }
}
