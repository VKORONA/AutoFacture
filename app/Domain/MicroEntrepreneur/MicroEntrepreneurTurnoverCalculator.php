<?php

namespace Crater\Domain\MicroEntrepreneur;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Crater\Models\MicroEntrepreneurSetting;
use Crater\Models\MicroTurnoverAdjustment;
use Crater\Models\Payment;
use Illuminate\Support\Collection;

final class MicroEntrepreneurTurnoverCalculator
{
    public function __construct(
        private readonly MicroEntrepreneurRateResolver $rateResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function calculate(
        int $companyId,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $settings = MicroEntrepreneurSetting::forCompany($companyId);
        $turnover = $this->emptyMonthlyTurnover($start, $end);
        $unclassifiedAmount = 0;
        $unclassifiedCount = 0;

        $payments = Payment::query()
            ->where('company_id', $companyId)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->with(['invoice.items'])
            ->orderBy('payment_date')
            ->get();

        foreach ($payments as $payment) {
            $month = Carbon::parse($payment->payment_date)->format('Y-m');
            $baseAmount = $this->paymentBaseAmount($payment);
            $invoice = $payment->invoice;

            if (! $invoice || $baseAmount === 0) {
                $unclassifiedAmount += $baseAmount;
                $unclassifiedCount++;

                continue;
            }

            $allocations = $this->allocatePaymentToActivities($invoice, $baseAmount);

            if ($allocations === []) {
                $unclassifiedAmount += $baseAmount;
                $unclassifiedCount++;

                continue;
            }

            foreach ($allocations as $activity => $amount) {
                $turnover[$month][$activity] += $amount;
            }
        }

        $adjustments = MicroTurnoverAdjustment::query()
            ->where('company_id', $companyId)
            ->whereBetween('adjustment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('adjustment_date')
            ->get();

        foreach ($adjustments as $adjustment) {
            $month = $adjustment->adjustment_date->format('Y-m');
            $activity = $adjustment->business_activity_type->value;
            $turnover[$month][$activity] += (int) $adjustment->amount;
        }

        $months = [];
        $grandTotals = $this->emptyTotals();

        foreach ($turnover as $month => $activities) {
            $calculationDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            $monthResult = [
                'month' => $month,
                'label' => $calculationDate->translatedFormat('F Y'),
                'activities' => [],
                'totals' => $this->emptyTotals(),
            ];

            foreach (BusinessActivityType::cases() as $activityType) {
                $amount = (int) ($activities[$activityType->value] ?? 0);
                $rates = $this->rateResolver->resolve($activityType, $calculationDate, $settings);
                $social = (int) round($amount * $rates['social_rate']);
                $cfp = (int) round($amount * $rates['cfp_rate']);
                $incomeTax = (int) round($amount * $rates['income_tax_rate']);
                $totalDue = $social + $cfp + $incomeTax;

                $activityResult = [
                    'type' => $activityType->value,
                    'label' => $activityType->shortLabel(),
                    'turnover' => $amount,
                    'social_contributions' => $social,
                    'cfp' => $cfp,
                    'income_tax' => $incomeTax,
                    'total_due' => $totalDue,
                    'rates' => $rates,
                ];

                $monthResult['activities'][$activityType->value] = $activityResult;
                $this->addToTotals($monthResult['totals'], $activityResult);
                $this->addToTotals($grandTotals, $activityResult);
            }

            $months[] = $monthResult;
        }

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'settings' => [
                'enabled' => (bool) $settings->enabled,
                'declaration_frequency' => $settings->declaration_frequency,
                'cfp_profile' => $settings->cfp_profile,
                'versement_liberatoire' => (bool) $settings->versement_liberatoire,
                'acre_enabled' => (bool) $settings->acre_enabled,
                'acre_end_date' => $settings->acre_end_date?->toDateString(),
                'acre_rate_factor' => (float) $settings->acre_rate_factor,
                'rate_overrides' => $settings->rate_overrides ?? [],
                'rates_verified_at' => $settings->rates_verified_at?->toIso8601String(),
            ],
            'activity_options' => BusinessActivityType::options(),
            'months' => $months,
            'totals' => $grandTotals,
            'adjustments' => $adjustments->map(static function (MicroTurnoverAdjustment $adjustment): array {
                return [
                    'id' => $adjustment->id,
                    'adjustment_date' => $adjustment->adjustment_date->toDateString(),
                    'business_activity_type' => $adjustment->business_activity_type->value,
                    'amount' => (int) $adjustment->amount,
                    'label' => $adjustment->label,
                    'notes' => $adjustment->notes,
                ];
            })->values(),
            'unclassified_receipts' => [
                'count' => $unclassifiedCount,
                'amount' => $unclassifiedAmount,
            ],
            'urssaf' => [
                'automatic_submission_available' => (bool) config(
                    'micro-entrepreneur.urssaf_third_party_api.enabled',
                    false,
                ),
                'status' => config('micro-entrepreneur.urssaf_third_party_api.enabled', false)
                    ? 'configured'
                    : 'approval_required',
            ],
            'disclaimer' => 'Estimation préparatoire calculée à partir des encaissements enregistrés. Le décompte Urssaf reste la référence opposable.',
        ];
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function emptyMonthlyTurnover(
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        $lastMonth = $end->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $months[$cursor->format('Y-m')] = array_fill_keys(BusinessActivityType::values(), 0);
            $cursor->addMonth();
        }

        return $months;
    }

    private function paymentBaseAmount(Payment $payment): int
    {
        if ((int) $payment->base_amount !== 0) {
            return (int) $payment->base_amount;
        }

        return (int) round((int) $payment->amount * (float) ($payment->exchange_rate ?: 1));
    }

    /**
     * @return array<string, int>
     */
    private function allocatePaymentToActivities($invoice, int $paymentBaseAmount): array
    {
        $invoiceTotal = (int) ($invoice->base_total ?: $invoice->total);
        $invoiceSubTotal = (int) ($invoice->base_sub_total ?: $invoice->sub_total);
        $invoiceDiscount = (int) ($invoice->base_discount_val ?: $invoice->discount_val);
        $netTurnover = max(0, $invoiceSubTotal - $invoiceDiscount);

        if ($invoiceTotal <= 0 || $netTurnover <= 0 || $invoice->items->isEmpty()) {
            return [];
        }

        $lineTotals = $invoice->items->mapWithKeys(function ($item): array {
            $activity = BusinessActivityType::tryFrom(
                (string) ($item->business_activity_type ?: BusinessActivityType::SERVICE_BIC->value)
            ) ?? BusinessActivityType::SERVICE_BIC;
            $amount = (int) ($item->base_total ?: $item->total);

            return [$item->id => [
                'activity' => $activity->value,
                'amount' => max(0, $amount),
            ]];
        });
        $allocationBase = (int) $lineTotals->sum('amount');

        if ($allocationBase <= 0) {
            return [];
        }

        $declaredTurnover = (int) round($paymentBaseAmount * $netTurnover / $invoiceTotal);
        $byActivity = $lineTotals
            ->groupBy('activity')
            ->map(static function (Collection $lines): int {
                return (int) $lines->sum('amount');
            });
        $result = [];
        $remaining = $declaredTurnover;
        $activities = $byActivity->keys()->values();

        foreach ($activities as $index => $activity) {
            $isLast = $index === $activities->count() - 1;
            $amount = $isLast
                ? $remaining
                : (int) round($declaredTurnover * $byActivity[$activity] / $allocationBase);
            $result[$activity] = $amount;
            $remaining -= $amount;
        }

        return $result;
    }

    /**
     * @return array{turnover: int, social_contributions: int, cfp: int, income_tax: int, total_due: int}
     */
    private function emptyTotals(): array
    {
        return [
            'turnover' => 0,
            'social_contributions' => 0,
            'cfp' => 0,
            'income_tax' => 0,
            'total_due' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $totals
     * @param  array<string, mixed>  $activity
     */
    private function addToTotals(array &$totals, array $activity): void
    {
        foreach (array_keys($this->emptyTotals()) as $key) {
            $totals[$key] += (int) $activity[$key];
        }
    }
}
