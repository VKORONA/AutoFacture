<?php

namespace Crater\Console\Commands;

use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Illuminate\Console\Command;

class ApplyFrenchDefaults extends Command
{
    protected $signature = 'autofacture:apply-french-defaults {--company= : Identifiant d’une entreprise précise}';

    protected $description = 'Applique les paramètres français recommandés à AutoFacture';

    public function handle()
    {
        $companies = Company::query()
            ->when($this->option('company'), function ($query, $companyId) {
                $query->whereKey($companyId);
            })
            ->get();

        if ($companies->isEmpty()) {
            $this->warn('Aucune entreprise à configurer.');
            return 0;
        }

        foreach ($companies as $company) {
            CompanySetting::setSettings($this->settings(), $company->id);
            $this->info("Paramètres français appliqués à {$company->name} (ID {$company->id}).");
        }

        return 0;
    }

    private function settings(): array
    {
        $companyAddressFormat = '<h3><strong>{COMPANY_NAME}</strong></h3>'
            .'<p>{COMPANY_ADDRESS_STREET_1}</p>'
            .'<p>{COMPANY_ADDRESS_STREET_2}</p>'
            .'<p>{COMPANY_ZIP_CODE} {COMPANY_CITY}</p>'
            .'<p>{COMPANY_COUNTRY}</p>'
            .'<p>{COMPANY_PHONE}</p>'
            .'<p>{COMPANY_LEGAL_MENTIONS}</p>';

        $billingAddressFormat = '<h3>{BILLING_ADDRESS_NAME}</h3>'
            .'<p>{BILLING_ADDRESS_STREET_1}</p>'
            .'<p>{BILLING_ADDRESS_STREET_2}</p>'
            .'<p>{BILLING_ZIP_CODE} {BILLING_CITY}</p>'
            .'<p>{BILLING_COUNTRY}</p>'
            .'<p>{BILLING_PHONE}</p>'
            .'<p>{CUSTOMER_LEGAL_MENTIONS}</p>';

        return [
            'time_zone' => 'Europe/Paris',
            'language' => 'fr',
            'fiscal_year' => '1-12',
            'carbon_date_format' => 'd/m/Y',
            'moment_date_format' => 'DD/MM/YYYY',
            'notification_email' => 'no-reply@autofacture.local',
            'invoice_number_format' => '{{SERIES:FAC}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_number_format' => '{{SERIES:DEV}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'payment_number_format' => '{{SERIES:REG}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_set_expiry_date_automatically' => 'YES',
            'estimate_expiry_date_days' => 30,
            'invoice_set_due_date_automatically' => 'YES',
            'invoice_due_date_days' => 30,
            'retrospective_edits' => 'disable_on_invoice_sent',
            'invoice_email_attachment' => 'YES',
            'estimate_email_attachment' => 'YES',
            'invoice_company_address_format' => $companyAddressFormat,
            'estimate_company_address_format' => $companyAddressFormat,
            'payment_company_address_format' => $companyAddressFormat,
            'invoice_billing_address_format' => $billingAddressFormat,
            'estimate_billing_address_format' => $billingAddressFormat,
            'payment_from_customer_address_format' => $billingAddressFormat,
            'invoice_mail_body' => 'Bonjour,<br><br>Veuillez trouver ci-joint la facture émise par <b>{COMPANY_NAME}</b>.<br><br>Cordialement.',
            'estimate_mail_body' => 'Bonjour,<br><br>Veuillez trouver ci-joint notre devis émis par <b>{COMPANY_NAME}</b>.<br><br>Cordialement.',
            'payment_mail_body' => 'Bonjour,<br><br>Nous vous remercions pour votre règlement. Vous trouverez le reçu en pièce jointe.<br><br>Cordialement.',
        ];
    }
}
