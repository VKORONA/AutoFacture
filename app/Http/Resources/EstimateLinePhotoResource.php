<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EstimateLinePhotoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'line_uuid' => $this->line_uuid,
            'original_name' => $this->original_name,
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
            'width' => $this->width,
            'height' => $this->height,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'web_size_bytes' => $this->web_size_bytes,
            'pdf_size_bytes' => $this->pdf_size_bytes,
            'thumbnail_url' => route('estimate-assets.photos.show', [
                'photo' => $this->id,
                'variant' => 'thumbnail',
            ]),
            'preview_url' => route('estimate-assets.photos.show', [
                'photo' => $this->id,
                'variant' => 'preview',
            ]),
        ];
    }
}
