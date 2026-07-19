<?php

namespace Crater\Domain\Estimates;

use Crater\Models\Estimate;
use Crater\Models\EstimateAttachment;
use Crater\Models\EstimateLinePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class EstimateAssetService
{
    public const MAX_PHOTOS_PER_LINE = 4;

    public function storeLinePhoto(
        UploadedFile $file,
        int $companyId,
        int $userId,
        string $draftToken,
        string $lineUuid,
        ?Estimate $estimate = null,
    ): EstimateLinePhoto {
        $query = EstimateLinePhoto::query()
            ->where('company_id', $companyId)
            ->where('line_uuid', $lineUuid);

        $estimate
            ? $query->where('estimate_id', $estimate->id)
            : $query->whereNull('estimate_id')->where('draft_token', $draftToken)->where('user_id', $userId);

        abort_if(
            $query->count() >= self::MAX_PHOTOS_PER_LINE,
            422,
            'Quatre photos maximum sont autorisées par ligne de devis.'
        );

        $disk = 'local';
        $assetId = (string) Str::uuid();
        $baseDirectory = sprintf(
            'estimate-assets/%d/%s/photos/%s',
            $companyId,
            $estimate?->id ?? $draftToken,
            $lineUuid,
        );

        $image = Image::make($file->getRealPath())->orientate();
        $normalized = clone $image;
        $preview = clone $image;
        $thumbnail = clone $image;

        $this->normalizeToCanvas($normalized, 1600, 1200);
        $this->normalizeToCanvas($preview, 800, 600);
        $this->normalizeToCanvas($thumbnail, 240, 180);

        $imagePath = $baseDirectory.'/'.$assetId.'.jpg';
        $previewPath = $baseDirectory.'/'.$assetId.'-preview.jpg';
        $thumbnailPath = $baseDirectory.'/'.$assetId.'-thumb.jpg';

        Storage::disk($disk)->put($imagePath, (string) $normalized->encode('jpg', 86));
        Storage::disk($disk)->put($previewPath, (string) $preview->encode('jpg', 84));
        Storage::disk($disk)->put($thumbnailPath, (string) $thumbnail->encode('jpg', 82));

        return EstimateLinePhoto::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'estimate_id' => $estimate?->id,
            'line_uuid' => $lineUuid,
            'draft_token' => $estimate ? null : $draftToken,
            'disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => 'image/jpeg',
            'image_path' => $imagePath,
            'preview_path' => $previewPath,
            'thumbnail_path' => $thumbnailPath,
            'size_bytes' => Storage::disk($disk)->size($imagePath),
            'width' => 1600,
            'height' => 1200,
            'sort_order' => (int) ($query->max('sort_order') ?? -1) + 1,
        ]);
    }

    public function storeAttachment(
        UploadedFile $file,
        int $companyId,
        int $userId,
        string $draftToken,
        ?Estimate $estimate = null,
    ): EstimateAttachment {
        $disk = 'local';
        $assetId = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $storedName = $assetId.'.'.$extension;
        $baseDirectory = sprintf(
            'estimate-assets/%d/%s/attachments',
            $companyId,
            $estimate?->id ?? $draftToken,
        );
        $path = $file->storeAs($baseDirectory, $storedName, $disk);

        abort_if($path === false, 500, 'La pièce annexe n’a pas pu être enregistrée.');

        $sortOrder = EstimateAttachment::query()
            ->where('company_id', $companyId)
            ->when(
                $estimate,
                fn ($query) => $query->where('estimate_id', $estimate->id),
                fn ($query) => $query->whereNull('estimate_id')->where('draft_token', $draftToken)->where('user_id', $userId),
            )
            ->max('sort_order');

        return EstimateAttachment::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'estimate_id' => $estimate?->id,
            'draft_token' => $estimate ? null : $draftToken,
            'disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'sort_order' => (int) ($sortOrder ?? -1) + 1,
        ]);
    }

    public function claimDraftAssets(Estimate $estimate, string $draftToken, int $userId): void
    {
        EstimateLinePhoto::query()
            ->where('company_id', $estimate->company_id)
            ->where('user_id', $userId)
            ->whereNull('estimate_id')
            ->where('draft_token', $draftToken)
            ->whereIn('line_uuid', $estimate->items()->pluck('line_uuid'))
            ->update([
                'estimate_id' => $estimate->id,
                'draft_token' => null,
                'updated_at' => now(),
            ]);

        EstimateAttachment::query()
            ->where('company_id', $estimate->company_id)
            ->where('user_id', $userId)
            ->whereNull('estimate_id')
            ->where('draft_token', $draftToken)
            ->update([
                'estimate_id' => $estimate->id,
                'draft_token' => null,
                'updated_at' => now(),
            ]);
    }

    public function removePhotosForDeletedLines(Estimate $estimate): void
    {
        $validLineUuids = $estimate->items()->pluck('line_uuid');

        EstimateLinePhoto::query()
            ->where('estimate_id', $estimate->id)
            ->whereNotIn('line_uuid', $validLineUuids)
            ->get()
            ->each(fn (EstimateLinePhoto $photo) => $this->deletePhoto($photo));
    }

    public function deleteAssetsForEstimate(Estimate $estimate): void
    {
        EstimateLinePhoto::query()
            ->where('estimate_id', $estimate->id)
            ->get()
            ->each(fn (EstimateLinePhoto $photo) => $this->deletePhoto($photo));

        EstimateAttachment::query()
            ->where('estimate_id', $estimate->id)
            ->get()
            ->each(fn (EstimateAttachment $attachment) => $this->deleteAttachment($attachment));
    }

    public function deletePhoto(EstimateLinePhoto $photo): void
    {
        Storage::disk($photo->disk)->delete([
            $photo->image_path,
            $photo->preview_path,
            $photo->thumbnail_path,
        ]);

        $photo->delete();
    }

    public function deleteAttachment(EstimateAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }

    private function normalizeToCanvas($image, int $width, int $height): void
    {
        $image->resize($width, $height, function ($constraint): void {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->resizeCanvas($width, $height, 'center', false, '#ffffff');
    }
}
