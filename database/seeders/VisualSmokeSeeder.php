<?php

namespace Database\Seeders;

use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Models\Address;
use Crater\Models\CompanySetting;
use Crater\Models\Currency;
use Crater\Models\Customer;
use Crater\Models\Invoice;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use LogicException;

class VisualSmokeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Le jeu de données visuel ne peut être chargé qu’en environnement local ou de test.');
        }

        $user = User::query()->where('email', 'admin@autofacture.local')->firstOrFail();
        $company = $user->companies()->firstOrFail();
        $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

        $user->setSettings(['language' => 'fr']);

        $company->forceFill([
            'name' => 'BATI-MESURE Démonstration',
            'legal_form' => 'Entreprise individuelle',
            'siren' => '123456789',
            'siret' => '12345678900012',
            'vat_number' => 'FR32123456789',
            'ape_code' => '7120B',
            'rcs_city' => 'Toulouse',
            'iban' => 'FR7630006000011234567890189',
            'bic' => 'AGRIFRPP',
        ])->save();

        $companyAddress = $company->address()->first();

        if (! $companyAddress) {
            $companyAddress = new Address(['company_id' => $company->id]);
        }

        $companyAddress->forceFill([
            'company_id' => $company->id,
            'name' => 'BATI-MESURE Démonstration',
            'address_street_1' => '12 rue des Contrôles',
            'address_street_2' => null,
            'city' => 'Bouloc',
            'state' => 'Haute-Garonne',
            'zip' => '31620',
            'country_id' => 1,
            'phone' => '05 61 00 00 00',
        ])->save();

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'creator_id' => $user->id,
            'currency_id' => $currency->id,
            'name' => 'Résidence Les Jardins',
            'company_name' => 'SCI Les Jardins',
            'contact_name' => 'Mme Martin',
            'email' => 'contact@les-jardins.example',
            'phone' => '05 61 11 22 33',
            'siren' => '987654321',
            'siret' => '98765432100019',
            'vat_number' => 'FR12987654321',
            'ape_code' => '6820A',
            'customer_type' => 'business',
        ]);

        Address::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => Address::BILLING_TYPE,
            'name' => 'SCI Les Jardins',
            'address_street_1' => '8 avenue des Acacias',
            'city' => 'Toulouse',
            'state' => 'Haute-Garonne',
            'zip' => '31000',
            'country_id' => 1,
            'phone' => '05 61 11 22 33',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'creator_id' => $user->id,
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'invoice_number' => 'FAC-VISUEL-000001',
            'reference_number' => 'DOSSIER-ACOUSTIQUE-2026-001',
            'sequence_number' => 1,
            'customer_sequence_number' => 1,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
            'status' => Invoice::STATUS_SENT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'sent' => true,
            'viewed' => false,
            'tax_per_item' => 'YES',
            'discount_per_item' => 'NO',
            'exchange_rate' => 1,
            'sub_total' => 120000,
            'tax' => 24000,
            'total' => 144000,
            'due_amount' => 144000,
            'base_sub_total' => 120000,
            'base_tax' => 24000,
            'base_total' => 144000,
            'base_due_amount' => 144000,
            'discount' => 0,
            'discount_val' => 0,
            'base_discount_val' => 0,
            'notes' => 'Contrôle acoustique réglementaire — bâtiment A. Rapport et mesures inclus.',
            'template_name' => 'invoice1',
        ]);

        Invoice::createItems($invoice, [[
            'item_id' => null,
            'name' => 'Contrôle acoustique réglementaire',
            'description' => 'Échantillonnage, mesures in situ et établissement du rapport de conformité.',
            'quantity' => 1,
            'price' => 120000,
            'discount_type' => 'fixed',
            'discount_val' => 0,
            'discount' => 0,
            'tax' => 24000,
            'total' => 144000,
            'taxes' => [],
        ]]);

        app(InvoiceFinalizer::class)->finalize($invoice, $user);

        CompanySetting::setSettings([
            'language' => 'fr',
            'currency' => (string) $currency->id,
        ], $company->id);
        Setting::setSetting('profile_complete', 'COMPLETED');
        Storage::disk('local')->put('database_created', 'database_created');
        Storage::disk('local')->put('visual-smoke.json', json_encode([
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ], JSON_THROW_ON_ERROR));
    }
}
