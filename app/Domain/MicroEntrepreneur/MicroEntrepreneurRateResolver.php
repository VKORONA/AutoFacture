<?php

namespace Crater\Domain\MicroEntrepreneur;

use Carbon\CarbonInterface;
use Crater\Models\MicroEntrepreneurSetting;
use RuntimeException;

final class MicroEntrepreneurRateResolver
{
    /**
     * @return array{
     *     effective_from: string,
     *     social_rate: float,
     *     standard_social_rate: float,
     *     cfp_rate: float,
     *     income_tax_rate: float,
     *     acre_applied: bool,
     *     source: string
     * }
     */
    public function resolve(
        BusinessActivityType $activityType,
        CarbonInterface $date,
        MicroEntrepreneurSetting $settings,
    ): array {
        $effectiveFrom = $this->effectiveDate($date);
        $table = config("micro-entrepreneur.rate_tables.{$effectiveFrom}");

        if (! is_array($table) || ! isset($table[$activityType->value])) {
            throw new RuntimeException(
                "Aucun barème micro-entrepreneur n’est configuré pour la date {$date->toDateString()}."
            );
        }

        $activityRates = $table[$activityType->value];
        $overrides = $settings->rate_overrides ?? [];
        $override = $overrides[$activityType->value] ?? [];
        $standardSocialRate = (float) ($override['social'] ?? $activityRates['social']);
        $incomeTaxRate = (float) ($override['income_tax'] ?? $activityRates['income_tax']);
        $acreApplied = $settings->acre_enabled
            && $settings->acre_end_date
            && $date->toDateString() <= $settings->acre_end_date->toDateString();
        $socialRate = $acreApplied
            ? $standardSocialRate * (float) $settings->acre_rate_factor
            : $standardSocialRate;

        return [
            'effective_from' => $effectiveFrom,
            'social_rate' => $socialRate,
            'standard_social_rate' => $standardSocialRate,
            'cfp_rate' => $this->cfpRate($activityType, $settings),
            'income_tax_rate' => $settings->versement_liberatoire ? $incomeTaxRate : 0.0,
            'acre_applied' => $acreApplied,
            'source' => 'Barème AutoFacture versionné — à rapprocher du décompte Urssaf',
        ];
    }

    private function effectiveDate(CarbonInterface $date): string
    {
        $dates = array_keys(config('micro-entrepreneur.rate_tables', []));
        rsort($dates);

        foreach ($dates as $effectiveFrom) {
            if ($date->toDateString() >= $effectiveFrom) {
                return $effectiveFrom;
            }
        }

        throw new RuntimeException(
            "Aucun barème micro-entrepreneur antérieur au {$date->toDateString()} n’est configuré."
        );
    }

    private function cfpRate(
        BusinessActivityType $activityType,
        MicroEntrepreneurSetting $settings,
    ): float {
        if (in_array($activityType, [
            BusinessActivityType::SERVICE_BNC,
            BusinessActivityType::SERVICE_BNC_CIPAV,
        ], true)) {
            return (float) config('micro-entrepreneur.cfp_rates.liberal', 0.002);
        }

        return (float) config(
            'micro-entrepreneur.cfp_rates.'.$settings->cfp_profile,
            0.001,
        );
    }
}
