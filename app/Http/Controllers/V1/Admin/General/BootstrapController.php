<?php

namespace Crater\Http\Controllers\V1\Admin\General;

use Crater\Domain\FrenchInvoicing\FrenchCompanySetup;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\CompanyResource;
use Crater\Http\Resources\UserResource;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\Currency;
use Crater\Models\Module;
use Crater\Models\Setting;
use Crater\Traits\GeneratesMenuTrait;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class BootstrapController extends Controller
{
    use GeneratesMenuTrait;

    public function __invoke(Request $request, FrenchCompanySetup $companySetup)
    {
        $currentUser = $request->user();
        $currentUserSettings = $currentUser->getAllSettings();
        $mainMenu = $this->generateMenu('main_menu', $currentUser);
        $settingMenu = $this->generateMenu('setting_menu', $currentUser);
        $companies = $currentUser->companies;
        $currentCompany = Company::find($request->header('company'));

        if ((! $currentCompany) || ! $currentUser->hasCompany($currentCompany->id)) {
            $currentCompany = $currentUser->companies()->firstOrFail();
        }

        $currentCompanySettings = CompanySetting::getAllSettings($currentCompany->id);
        $currentCompanyCurrency = $currentCompanySettings->has('currency')
            ? Currency::find($currentCompanySettings->get('currency'))
            : Currency::first();

        BouncerFacade::refreshFor($currentUser);

        $globalSettings = Setting::getSettings([
            'api_token',
            'admin_portal_theme',
            'admin_portal_logo',
            'login_page_logo',
            'login_page_heading',
            'login_page_description',
            'admin_page_title',
            'copyright_text',
        ]);

        return response()->json([
            'current_user' => new UserResource($currentUser),
            'current_user_settings' => $currentUserSettings,
            'current_user_abilities' => $currentUser->getAbilities(),
            'companies' => CompanyResource::collection($companies),
            'current_company' => new CompanyResource($currentCompany),
            'current_company_settings' => $currentCompanySettings,
            'current_company_currency' => $currentCompanyCurrency,
            'company_setup' => $companySetup->status($currentCompany),
            'config' => config('crater'),
            'global_settings' => $globalSettings,
            'main_menu' => $mainMenu,
            'setting_menu' => $settingMenu,
            'modules' => Module::where('enabled', true)->pluck('name'),
        ]);
    }
}
