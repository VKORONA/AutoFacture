<?php

namespace Crater\Domain\Estimates;

use Crater\Models\Estimate;
use Crater\Models\EstimateAttachment;
use Crater\Models\EstimateLinePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic as ImageManager;

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

        $source = ImageManager::make($file->getRealPath())->orientate();
        $main = clone $source;
        $preview = clone $source;
        $thumbnail = clone $source;
        $pdf = clone $source;

        $this->normalizeToCanvas($main, 1600, 1200);
        $this->normalizeToCanvas($preview, 800, 600);
        $this->normalizeToCanvas($thumbnail, 320, 240);
        $this->normalizeToCanvas($pdf, 1600, 1200);

        $webFormat = function_exists('imagewebp') ? 'webp' : 'jpg';
        $webExtension = $webFormat === 'webp' ? 'webp' : 'jpg';
        $webMime = $webFormat === 'webp' ? 'image/webp' : 'image/jpeg';

        $mainBytes = $this->encodeToTarget($main, $webFormat, 80, 700 * 1024, 62);
        $previewBytes = $this->encodeToTarget($preview, $webFormat, 78, 260 * 1024, 58);
        $thumbnailBytes = $this->encodeToTarget($thumbnail, $webFormat, 72, 80 * 1024, 52);
        $pdfBytes = $this->encodeToTarget($pdf, 'jpg', 84, 900 * 1024, 68);
        $checksum = hash('sha256', $mainBytes);

        abort_if(
            (clone $query)->where('checksum_sha256', $checksum)->exists(),
            422,
            'Cette photo est déjà associée à cette ligne de devis.'
        );

        $imagePath = $baseDirectory.'/'.$assetId.'.'.$webExtension;
        $previewPath = $baseDirectory.'/'.$assetId.'-preview.'.$webExtension;
        $thumbnailPath = $baseDirectory.'/'.$assetId.'-thumb.'.$webExtension;
        $pdfPath = $baseDirectory.'/'.$assetId.'-pdf.jpg';

        Storage::disk($disk)->put($imagePath, $mainBytes);
        Storage::disk($disk)->put($previewPath, $previewBytes);
        Storage::disk($disk)->put($thumbnailPath, $thumbnailBytes);
        Storage::disk($disk)->put($pdfPath, $pdfBytes);

        $webSize = strlen($mainBytes) + strlen($previewBytes) + strlen($thumbnailBytes);

        return EstimateLinePhoto::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'estimate_id' => $estimate?->id,
            'line_uuid' => $lineUuid,
            'draft_token' => $estimate ? null : $draftToken,
            'disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $webMime,
            'image_path' => $imagePath,
            'preview_path' => $previewPath,
            'thumbnail_path' => $thumbnailPath,
            'pdf_path' => $pdfPath,
            'checksum_sha256' => $checksum,
            'size_bytes' => strlen($mainBytes),
            'web_size_bytes' => $webSize,
            'pdf_size_bytes' => strlen($pdfBytes),
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
        Storage::disk($photo->disk)->delete(array_values(array_filter([
            $photo->image_path,
            $photo->preview_path,
            $photo->thumbnail_path,
            $photo->pdf_path,
        ])));

        $photo->delete();
    }

    public function deleteAttachment(EstimateAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }

    private function normalizeToCanvas(Image $image, int $width, int $height): void
    {
        $image->resize($width, $height, function ($constraint): void {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->resizeCanvas($width, $height, 'center', false, '#ffffff');
    }

    private function encodeToTarget(
        Image $image,
        string $format,
        int $startQuality,
        int $targetBytes,
        int $minimumQuality,
    ): string {
        $quality = $startQuality;
        $encoded = (string) (clone $image)->encode($format, $quality);

        while (strlen($encoded) > $targetBytes && $quality > $minimumQuality) {
            $quality = max($minimumQuality, $quality - 4);
            $encoded = (string) (clone $image)->encode($format, $quality);
        }

        return $encoded;
    }
}
