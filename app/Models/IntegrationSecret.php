<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationSecret extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['secret'];

    protected $casts = [
        'secret' => 'encrypted:array',
        'last_used_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
