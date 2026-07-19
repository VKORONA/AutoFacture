<?php

namespace Crater\Http\Controllers\V1\Admin\Settings;

use Crater\Domain\FrenchInvoicing\FrenchCompanySetup;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\AvatarRequest;
use Crater\Http\Requests\CompanyLogoRequest;
use Crater\Http\Requests\CompanyRequest;
use Crater\Http\Requests\ProfileRequest;
use Crater\Http\Resources\CompanyResource;
use Crater\Http\Resources\UserResource;
use Crater\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function getUser(Request $request)
    {
        return new UserResource($request->user());
    }

    public function updateProfile(ProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function updateCompany(CompanyRequest $request, FrenchCompanySetup $companySetup)
    {
        $company = Company::findOrFail($request->header('company'));
        $this->authorize('manage company', $company);

        DB::transaction(function () use ($company, $companySetup, $request): void {
            $company->update($request->getCompanyPayload());
            $company->address()->updateOrCreate(
                ['company_id' => $company->id],
                $request->validated('address')
            );
            $companySetup->synchronizeVatDefaults($company->refresh());
        });

        $company->refresh()->load('address');
        $resource = new CompanyResource($company);

        return $resource->additional([
            'company_setup' => $companySetup->status($company),
        ]);
    }

    public function uploadCompanyLogo(CompanyLogoRequest $request)
    {
        $company = Company::findOrFail($request->header('company'));
        $this->authorize('manage company', $company);
        $data = json_decode($request->company_logo);

        if (isset($request->is_company_logo_removed) && (bool) $request->is_company_logo_removed) {
            $company->clearMediaCollection('logo');
        }

        if ($data) {
            $company->clearMediaCollection('logo');
            $company->addMediaFromBase64($data->data)
                ->usingFileName($data->name)
                ->toMediaCollection('logo');
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function uploadAvatar(AvatarRequest $request)
    {
        $user = auth()->user();

        if (isset($request->is_admin_avatar_removed) && (bool) $request->is_admin_avatar_removed) {
            $user->clearMediaCollection('admin_avatar');
        }

        if ($user && $request->hasFile('admin_avatar')) {
            $user->clearMediaCollection('admin_avatar');
            $user->addMediaFromRequest('admin_avatar')
                ->toMediaCollection('admin_avatar');
        }

        if ($user && $request->has('avatar')) {
            $data = json_decode($request->avatar);
            $user->clearMediaCollection('admin_avatar');
            $user->addMediaFromBase64($data->data)
                ->usingFileName($data->name)
                ->toMediaCollection('admin_avatar');
        }

        return new UserResource($user);
    }
}
