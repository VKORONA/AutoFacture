<?php

use Crater\Models\CompanySetting;
use Crater\Models\Currency;
use Crater\Models\CustomField;
use Crater\Models\Setting;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;

function get_company_setting($key, $company_id)
{
    if (\Storage::disk('local')->has('database_created')) {
        return CompanySetting::getSetting($key, $company_id);
    }
}

function get_app_setting($key)
{
    if (\Storage::disk('local')->has('database_created')) {
        return Setting::getSetting($key);
    }
}

function get_page_title($company_id)
{
    $routeName = Route::currentRouteName();
    $pageTitle = null;
    $defaultPageTitle = 'AutoFacture - Devis et facturation';

    if (\Storage::disk('local')->has('database_created')) {
        if ($routeName === 'customer.dashboard') {
            $pageTitle = CompanySetting::getSetting('customer_portal_page_title', $company_id);

            return $pageTitle ?: $defaultPageTitle;
        }

        $pageTitle = Setting::getSetting('admin_page_title');

        return $pageTitle ?: $defaultPageTitle;
    }

    return $defaultPageTitle;
}

function set_active($path, $active = 'active')
{
    return call_user_func_array('Request::is', (array) $path) ? $active : '';
}

function is_url($path)
{
    return call_user_func_array('Request::is', (array) $path);
}

function getCustomFieldValueKey(string $type)
{
    return match ($type) {
        'Phone', 'Number' => 'number_answer',
        'Switch' => 'boolean_answer',
        'Date' => 'date_answer',
        'Time' => 'time_answer',
        'DateTime' => 'date_time_answer',
        default => 'string_answer',
    };
}

function format_money_pdf($money, $currency = null)
{
    $money /= 100;

    if (! $currency) {
        $companyId = app(\Crater\Tenancy\CompanyContext::class)->idOrNull() ?: request()->header('company');
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $companyId));
    }

    $formattedMoney = number_format(
        $money,
        $currency->precision,
        $currency->decimal_separator,
        $currency->thousand_separator
    );

    if ($currency->swap_currency_symbol) {
        return $formattedMoney.'<span style="font-family: DejaVu Sans;">'.$currency->symbol.'</span>';
    }

    return '<span style="font-family: DejaVu Sans;">'.$currency->symbol.'</span>'.$formattedMoney;
}

function clean_slug($model, $title, $id = 0)
{
    $slug = Str::upper('CUSTOM_'.$model.'_'.Str::slug($title, '_'));
    $allSlugs = getRelatedSlugs($model, $slug, $id);

    if (! $allSlugs->contains('slug', $slug)) {
        return $slug;
    }

    for ($i = 1; $i <= 10; $i++) {
        $newSlug = $slug.'_'.$i;

        if (! $allSlugs->contains('slug', $newSlug)) {
            return $newSlug;
        }
    }

    throw new RuntimeException('Impossible de créer un identifiant unique.');
}

function getRelatedSlugs($type, $slug, $id = 0)
{
    return CustomField::select('slug')
        ->where('slug', 'like', $slug.'%')
        ->where('model_type', $type)
        ->where('id', '<>', $id)
        ->get();
}

function respondJson($error, $message)
{
    return response()->json([
        'error' => $error,
        'message' => $message,
    ], 422);
}

if (! function_exists('vite_asset')) {
    function vite_asset(string $asset): string
    {
        try {
            return Vite::asset($asset);
        } catch (Throwable $exception) {
            $manifestPath = public_path('build/manifest.json');

            if (is_file($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                $entry = $manifest[$asset]['file'] ?? null;

                if ($entry) {
                    return asset('build/'.$entry);
                }
            }

            return asset($asset);
        }
    }
}
