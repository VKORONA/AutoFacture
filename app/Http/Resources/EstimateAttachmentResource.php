<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EstimateAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'sort_order' => $this->sort_order,
            'title' => $this->title,
            'description' => $this->description,
            'include_in_email' => $this->include_in_email,
            'download_url' => route('estimate-assets.attachments.download', [
                'attachment' => $this->id,
            ]),
        ];
    }
}
