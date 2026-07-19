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
            'web_size_bytes' => 'integer',
            'pdf_size_bytes' => 'integer',
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

    public function variantPath(string $variant = 'preview'): ?string
    {
        return match ($variant) {
            'thumbnail' => $this->thumbnail_path,
            'image' => $this->image_path,
            'pdf' => $this->pdf_path ?: $this->image_path,
            default => $this->preview_path,
        };
    }

    public function dataUri(string $variant = 'preview'): ?string
    {
        $path = $this->pdf_path && Storage::disk($this->disk)->exists($this->pdf_path)
            ? $this->pdf_path
            : $this->variantPath($variant);

        if (! $path || ! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode(
            Storage::disk($this->disk)->get($path)
        );
    }
}
