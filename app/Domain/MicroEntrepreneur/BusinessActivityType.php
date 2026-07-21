<?php

namespace Crater\Domain\MicroEntrepreneur;

enum BusinessActivityType: string
{
    case GOODS_BIC = 'goods_bic';
    case SERVICE_BIC = 'service_bic';
    case SERVICE_BNC = 'service_bnc';
    case SERVICE_BNC_CIPAV = 'service_bnc_cipav';

    public function label(): string
    {
        return match ($this) {
            self::GOODS_BIC => 'Vente de marchandises / fourniture de biens (BIC)',
            self::SERVICE_BIC => 'Prestation de services commerciale ou artisanale (BIC)',
            self::SERVICE_BNC => 'Activité libérale non réglementée (BNC)',
            self::SERVICE_BNC_CIPAV => 'Activité libérale réglementée relevant de la Cipav (BNC)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::GOODS_BIC => 'Ventes BIC',
            self::SERVICE_BIC => 'Services BIC',
            self::SERVICE_BNC => 'Services BNC',
            self::SERVICE_BNC_CIPAV => 'BNC Cipav',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
