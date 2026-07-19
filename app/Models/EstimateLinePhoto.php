<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EstimateLinePhoto extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataUri(string $variant = 'preview'): ?string
    {
        $path = match ($variant) {
            'thumbnail' => $this->thumbnail_path,
            'image' => $this->image_path,
            default => $this->preview_path,
        };

        if (! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode(
            Storage::disk($this->disk)->get($path)
        );
    }
}
