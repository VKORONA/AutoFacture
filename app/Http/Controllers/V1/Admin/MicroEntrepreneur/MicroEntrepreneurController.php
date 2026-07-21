<?php

namespace Crater\Http\Controllers\V1\Admin\MicroEntrepreneur;

use Carbon\Carbon;
use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Crater\Domain\MicroEntrepreneur\MicroEntrepreneurTurnoverCalculator;
use Crater\Http\Controllers\Controller;
use Crater\Models\MicroEntrepreneurSetting;
use Crater\Models\MicroTurnoverAdjustment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MicroEntrepreneurController extends Controller
{
    public function show(Request $request, MicroEntrepreneurTurnoverCalculator $calculator)
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'year' => ['nullable', 'integer', 'min:2026', 'max:2100'],
        ]);
        $year = (int) ($validated['year'] ?? now()->year);
        $start = isset($validated['start'])
            ? Carbon::parse($validated['start'])->startOfDay()
            : Carbon::create($year)->startOfYear();
        $end = isset($validated['end'])
            ? Carbon::parse($validated['end'])->endOfDay()
            : Carbon::create($year)->endOfYear();

        return response()->json(
            $calculator->calculate($this->companyId($request), $start, $end),
        );
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'declaration_frequency' => ['required', Rule::in(['monthly', 'quarterly'])],
            'cfp_profile' => ['required', Rule::in(['commercial', 'artisan', 'liberal'])],
            'versement_liberatoire' => ['required', 'boolean'],
            'acre_enabled' => ['required', 'boolean'],
            'acre_end_date' => ['nullable', 'date', 'required_if:acre_enabled,true'],
            'acre_rate_factor' => ['required', 'numeric', 'gt:0', 'lte:1'],
            'rate_overrides' => ['nullable', 'array'],
        ]);

        $settings = MicroEntrepreneurSetting::forCompany($this->companyId($request));
        $settings->fill($validated);
        $settings->rates_verified_at = now();
        $settings->save();

        return response()->json([
            'message' => 'Paramètres micro-entrepreneur enregistrés.',
            'settings' => $settings->fresh(),
        ]);
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'adjustment_date' => ['required', 'date'],
            'business_activity_type' => ['required', Rule::in(BusinessActivityType::values())],
            'amount' => ['required', 'integer', 'not_in:0'],
            'label' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $adjustment = MicroTurnoverAdjustment::create([
            ...$validated,
            'company_id' => $this->companyId($request),
            'creator_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Ajustement de chiffre d’affaires ajouté.',
            'adjustment' => $adjustment,
        ], 201);
    }

    public function destroyAdjustment(Request $request, MicroTurnoverAdjustment $adjustment)
    {
        abort_unless($adjustment->company_id === $this->companyId($request), 404);
        $adjustment->delete();

        return response()->json([
            'message' => 'Ajustement supprimé.',
        ]);
    }

    public function export(Request $request, MicroEntrepreneurTurnoverCalculator $calculator): StreamedResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2026', 'max:2100'],
        ]);
        $year = (int) $validated['year'];
        $report = $calculator->calculate(
            $this->companyId($request),
            Carbon::create($year)->startOfYear(),
            Carbon::create($year)->endOfYear(),
        );

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Mois',
                'Catégorie',
                'CA encaissé HT',
                'Taux social',
                'Cotisations sociales',
                'CFP',
                'Versement libératoire',
                'Total estimé',
            ], ';');

            foreach ($report['months'] as $month) {
                foreach ($month['activities'] as $activity) {
                    fputcsv($stream, [
                        $month['label'],
                        $activity['label'],
                        number_format($activity['turnover'] / 100, 2, ',', ''),
                        number_format($activity['rates']['social_rate'] * 100, 2, ',', '').' %',
                        number_format($activity['social_contributions'] / 100, 2, ',', ''),
                        number_format($activity['cfp'] / 100, 2, ',', ''),
                        number_format($activity['income_tax'] / 100, 2, ',', ''),
                        number_format($activity['total_due'] / 100, 2, ',', ''),
                    ], ';');
                }
            }

            fclose($stream);
        }, 'declaration-micro-entrepreneur-'.$year.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function companyId(Request $request): int
    {
        $companyId = (int) $request->header('company');
        abort_unless($companyId > 0 && $request->user()->hasCompany($companyId), 403);

        return $companyId;
    }
}
