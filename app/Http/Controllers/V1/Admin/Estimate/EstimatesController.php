<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

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

    public function store(EstimatesRequest $request, FrenchCompanySetup $companySetup)
    {
        $this->authorize('create', Estimate::class);

        $company = Company::findOrFail((int) $request->header('company'));
        $companySetup->assertComplete($company);

        $estimate = DB::transaction(
            fn (): Estimate => Estimate::createEstimate($request)
        );

        if ($request->has('estimateSend')) {
            $estimate->send($request->title, $request->body);
        }

        GenerateEstimatePdfJob::dispatch($estimate);

        $estimate->load(['items.taxes', 'customer', 'taxes']);
        $resource = new EstimateResource($estimate);

        return $resource->additional([
            // Compatibilité avec le store Vue historique qui lisait response.data.estimate.
            'estimate' => $resource->resolve($request),
        ]);
    }

    public function show(Request $request, Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        return new EstimateResource($estimate);
    }

    public function update(EstimatesRequest $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);

        $estimate = DB::transaction(
            fn (): Estimate => $estimate->updateEstimate($request)
        );

        GenerateEstimatePdfJob::dispatch($estimate, true);

        return new EstimateResource($estimate);
    }

    public function delete(DeleteEstimatesRequest $request)
    {
        $this->authorize('delete multiple estimates');

        Estimate::destroy($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }
}
