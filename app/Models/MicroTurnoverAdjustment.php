<?php

namespace Crater\Models;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MicroTurnoverAdjustment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'adjustment_date' => 'date',
        'amount' => 'integer',
        'business_activity_type' => BusinessActivityType::class,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
