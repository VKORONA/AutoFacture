<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Crater\Domain\Estimates\EstimateAssetService;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\EstimateAttachmentResource;
use Crater\Http\Resources\EstimateLinePhotoResource;
use Crater\Models\Company;
use Crater\Models\Estimate;
use Crater\Models\EstimateAttachment;
use Crater\Models\EstimateLinePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EstimateAssetController extends Controller
{
    public function storePhoto(Request $request, EstimateAssetService $assets): EstimateLinePhotoResource
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'line_uuid' => ['required', 'uuid'],
            'draft_token' => ['required', 'uuid'],
            'estimate_id' => ['nullable', 'integer'],
        ]);

        $company = $this->company($request);
        $estimate = $this->estimate($request, $company, $validated['estimate_id'] ?? null);

        if ($estimate) {
            abort_unless(
                $estimate->items()->where('line_uuid', $validated['line_uuid'])->exists(),
                422,
                'Cette ligne ne fait pas partie du devis.'
            );
        }

        $photo = $assets->storeLinePhoto(
            $request->file('photo'),
            $company->id,
            $request->user()->id,
            $validated['draft_token'],
            $validated['line_uuid'],
            $estimate,
        );

        return new EstimateLinePhotoResource($photo);
    }

    public function destroyPhoto(
        Request $request,
        EstimateLinePhoto $photo,
        EstimateAssetService $assets,
    ): JsonResponse {
        $this->assertAssetAccess($request, $photo->company_id, $photo->user_id, $photo->estimate);
        $assets->deletePhoto($photo);

        return response()->json(['success' => true]);
    }

    public function showPhoto(Request $request, EstimateLinePhoto $photo, string $variant = 'preview')
    {
        $this->assertAssetAccess($request, $photo->company_id, $photo->user_id, $photo->estimate, readOnly: true);

        $path = match ($variant) {
            'thumbnail' => $photo->thumbnail_path,
            'image' => $photo->image_path,
            default => $photo->preview_path,
        };

        abort_unless(Storage::disk($photo->disk)->exists($path), 404);

        return response(Storage::disk($photo->disk)->get($path), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function storeAttachment(Request $request, EstimateAssetService $assets): EstimateAttachmentResource
    {
        $validated = $request->validate([
            'attachment' => [
                'required',
                'file',
                'max:15360',
                'mimes:pdf,jpg,jpeg,png,webp',
                Rule::notIn(['php', 'phar', 'exe', 'js', 'html']),
            ],
            'draft_token' => ['required', 'uuid'],
            'estimate_id' => ['nullable', 'integer'],
        ]);

        $company = $this->company($request);
        $estimate = $this->estimate($request, $company, $validated['estimate_id'] ?? null);

        $attachment = $assets->storeAttachment(
            $request->file('attachment'),
            $company->id,
            $request->user()->id,
            $validated['draft_token'],
            $estimate,
        );

        return new EstimateAttachmentResource($attachment);
    }

    public function destroyAttachment(
        Request $request,
        EstimateAttachment $attachment,
        EstimateAssetService $assets,
    ): JsonResponse {
        $this->assertAssetAccess($request, $attachment->company_id, $attachment->user_id, $attachment->estimate);
        $assets->deleteAttachment($attachment);

        return response()->json(['success' => true]);
    }

    public function downloadAttachment(Request $request, EstimateAttachment $attachment)
    {
        $this->assertAssetAccess(
            $request,
            $attachment->company_id,
            $attachment->user_id,
            $attachment->estimate,
            readOnly: true,
        );

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }

    private function company(Request $request): Company
    {
        return Company::query()
            ->whereKey($request->header('company'))
            ->whereHas('users', fn ($query) => $query->where('users.id', $request->user()->id))
            ->firstOrFail();
    }

    private function estimate(Request $request, Company $company, ?int $estimateId): ?Estimate
    {
        if (! $estimateId) {
            return null;
        }

        $estimate = Estimate::query()
            ->whereKey($estimateId)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $this->authorize('update', $estimate);

        return $estimate;
    }

    private function assertAssetAccess(
        Request $request,
        int $companyId,
        int $userId,
        ?Estimate $estimate,
        bool $readOnly = false,
    ): void {
        $belongsToCompany = $request->user()
            ->companies()
            ->where('companies.id', $companyId)
            ->exists();

        abort_unless($belongsToCompany, 403);

        if ($estimate) {
            $this->authorize($readOnly ? 'view' : 'update', $estimate);

            return;
        }

        abort_unless((int) $request->user()->id === $userId, 403);
    }
}
