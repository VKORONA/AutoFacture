<?php

namespace Crater\Http\Controllers\V1\Admin\Company;

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\CompaniesRequest;
use Crater\Http\Resources\CompanyResource;
use Crater\Models\Company;
use Crater\Models\User;
use Crater\Tenancy\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class CompaniesController extends Controller
{
    public function store(
        CompaniesRequest $request,
        FrenchCompanyDefaults $defaults,
        CompanyContext $context
    ) {
        $this->authorize('create company');

        $company = DB::transaction(function () use ($request, $defaults, $context): Company {
            $user = $request->user();
            $company = Company::create($request->getCompanyPayload());
            $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
            $company->save();

            $context->runWith($company->id, function () use ($request, $defaults, $user, $company): void {
                $company->setupDefaultData();
                $defaults->apply($company, $request->integer('currency'));
                $user->companies()->syncWithoutDetaching([$company->id]);
                BouncerFacade::scope()->to($company->id);
                $user->assign('super admin');

                if ($request->address) {
                    $company->address()->create($request->address);
                }
            });

            return $company;
        });

        return (new CompanyResource($company->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request)
    {
        $company = Company::findOrFail($request->header('company'));
        $this->authorize('delete company', $company);
        $user = $request->user();

        if ($request->name !== $company->name) {
            return respondJson('company_name_must_match_with_given_name', 'Company name must match with given name');
        }

        if ($user->loadCount('companies')->companies_count <= 1) {
            return respondJson('You_cannot_delete_all_companies', 'You cannot delete all companies');
        }

        $company->deleteCompany($user);

        return response()->json(['success' => true]);
    }

    public function transferOwnership(Request $request, User $user)
    {
        $company = Company::findOrFail($request->header('company'));
        $this->authorize('transfer company ownership', $company);

        DB::transaction(function () use ($company, $user): void {
            $user->companies()->syncWithoutDetaching([$company->id]);
            $company->update(['owner_id' => $user->id]);
            BouncerFacade::scope()->to($company->id);
            BouncerFacade::sync($user)->roles(['super admin']);
        });

        return response()->json(['success' => true]);
    }

    public function getUserCompanies(Request $request)
    {
        return CompanyResource::collection($request->user()->companies);
    }
}
