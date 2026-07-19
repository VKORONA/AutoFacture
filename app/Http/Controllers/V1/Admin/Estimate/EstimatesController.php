<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Crater\Domain\Estimates\EstimateAssetService;
use Crater\Domain\FrenchInvoicing\FrenchCompanySetup;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\DeleteEstimatesRequest;
use Crater\Http\Requests\EstimatesRequest;
use Crater\Http\Resources\EstimateResource;
use Crater\Jobs\GenerateEstimatePdfJob;
use Crater\Models\Company;
use Crater\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EstimatesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Estimate::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $estimates = Estimate::whereCompany()
            ->join('customers', 'customers.id', '=', 'estimates.customer_id')
            ->applyFilters($request->all())
            ->select('estimates.*', 'customers.name')
            ->latest()
            ->paginateData($limit);

        return (EstimateResource::collection($estimates))
            ->additional(['meta' => [
                'estimate_total_count' => Estimate::whereCompany()->count(),
            ]]);
    }

    public function store(
        EstimatesRequest $request,
        FrenchCompanySetup $companySetup,
        EstimateAssetService $assets,
    ) {
        $this->authorize('create', Estimate::class);

        $company = Company::findOrFail((int) $request->header('company'));
        $companySetup->assertComplete($company);
        $this->prepareItems($request);

        $estimate = DB::transaction(function () use ($request, $assets): Estimate {
            $estimate = Estimate::createEstimate($request);
            $draftToken = $request->input('asset_draft_token');

            if ($draftToken) {
                $assets->claimDraftAssets(
                    $estimate,
                    (string) $draftToken,
                    (int) $request->user()->id,
                );
            }

            return $estimate;
        });

        if ($request->has('estimateSend')) {
            $estimate->send($request->title, $request->body);
        }

        GenerateEstimatePdfJob::dispatch($estimate);

        $estimate->load(['items.taxes', 'customer', 'taxes']);
        $resource = new EstimateResource($estimate);

        return $resource->additional([
            'estimate' => $resource->resolve($request),
        ]);
    }

    public function show(Request $request, Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        return new EstimateResource($estimate);
    }

    public function update(
        EstimatesRequest $request,
        Estimate $estimate,
        EstimateAssetService $assets,
    ) {
        $this->authorize('update', $estimate);
        $this->prepareItems($request);

        $estimate = DB::transaction(function () use ($request, $estimate, $assets): Estimate {
            $updatedEstimate = $estimate->updateEstimate($request);
            $draftToken = $request->input('asset_draft_token');

            if ($draftToken) {
                $assets->claimDraftAssets(
                    $updatedEstimate,
                    (string) $draftToken,
                    (int) $request->user()->id,
                );
            }

            $assets->removePhotosForDeletedLines($updatedEstimate);

            return $updatedEstimate;
        });

        GenerateEstimatePdfJob::dispatch($estimate, true);

        return new EstimateResource($estimate);
    }

    public function delete(DeleteEstimatesRequest $request, EstimateAssetService $assets)
    {
        $this->authorize('delete multiple estimates');

        Estimate::query()
            ->whereIn('id', $request->ids)
            ->whereCompany()
            ->get()
            ->each(function (Estimate $estimate) use ($assets): void {
                $assets->deleteAssetsForEstimate($estimate);
                $estimate->delete();
            });

        return response()->json([
            'success' => true,
        ]);
    }

    private function prepareItems(EstimatesRequest $request): void
    {
        $items = collect($request->input('items', []))
            ->map(function (array $item): array {
                $item['line_uuid'] = $item['line_uuid'] ?? (string) Str::uuid();

                unset($item['line_photos']);

                return $item;
            })
            ->all();

        $request->merge(['items' => $items]);
    }
}
