<?php

namespace Crater\Traits;

use Carbon\Carbon;
use Crater\Domain\FrenchInvoicing\FrenchLegalMentionBuilder;
use Crater\Models\Address;
use Crater\Models\CompanySetting;
use Crater\Models\FileDisk;
use Illuminate\Support\Facades\App;

trait GeneratesPdfTrait
{
    public function getGeneratedPDFOrStream($collection_name)
    {
        $generatedPdf = $this->getGeneratedPDF($collection_name);

        if ($generatedPdf && $generatedPdf['path']) {
            $contents = @file_get_contents($generatedPdf['path']);

            if ($contents !== false) {
                return response()->make($contents, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$generatedPdf['file_name'].'"',
                ]);
            }
        }

        $locale = CompanySetting::getSetting('language', $this->company_id);
        App::setLocale($locale ?: config('app.locale'));
        $pdf = $this->getPDFData();

        return response()->make($pdf->stream(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this[$collection_name.'_number'].'.pdf"',
        ]);
    }

    public function getGeneratedPDF($collection_name)
    {
        try {
            $media = $this->getMedia($collection_name)->first();

            if (! $media) {
                return false;
            }

            $fileDiskId = $media->custom_properties['file_disk_id'] ?? null;
            $fileDisk = $fileDiskId ? FileDisk::find($fileDiskId) : null;

            if (! $fileDisk) {
                return false;
            }

            $fileDisk->setConfig();
            $path = $fileDisk->driver === 'local'
                ? $media->getPath()
                : $media->getTemporaryUrl(Carbon::now()->addMinutes(5));

            return collect([
                'path' => $path,
                'file_name' => $media->file_name,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function generatePDF($collection_name, $file_name, $deleteExistingFile = false)
    {
        $savePdfToDisk = CompanySetting::getSetting('save_pdf_to_disk', $this->company_id);

        if ($savePdfToDisk === 'NO') {
            return 0;
        }

        $fileDisk = FileDisk::whereSetAsDefault(true)->first();

        if (! $fileDisk) {
            return 'Aucun stockage de fichiers par défaut n’est configuré.';
        }

        $locale = CompanySetting::getSetting('language', $this->company_id);
        App::setLocale($locale ?: config('app.locale'));
        $pdf = $this->getPDFData();
        $temporaryDirectory = 'temp/'.$collection_name.'/'.$this->id;
        $temporaryPath = $temporaryDirectory.'/temp.pdf';

        \Storage::disk('local')->put($temporaryPath, $pdf->output());

        if ($deleteExistingFile) {
            $this->clearMediaCollection($collection_name);
        }

        $fileDisk->setConfig();
        $media = \Storage::disk('local')->path($temporaryPath);

        try {
            $this->addMedia($media)
                ->withCustomProperties(['file_disk_id' => $fileDisk->id])
                ->usingFileName($file_name.'.pdf')
                ->toMediaCollection($collection_name, config('filesystems.default'));

            \Storage::disk('local')->deleteDirectory($temporaryDirectory);
            return true;
        } catch (\Throwable $exception) {
            report($exception);
            return $exception->getMessage();
        }
    }

    public function getFieldsArray()
    {
        $customer = $this->customer;
        $shippingAddress = $customer->shippingAddress ?? new Address();
        $billingAddress = $customer->billingAddress ?? new Address();
        $companyAddress = $this->company->address ?? new Address();
        $mentionBuilder = app(FrenchLegalMentionBuilder::class);

        $escape = static function ($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $companyLegalMentions = implode('<br>', array_map(
            $escape,
            $mentionBuilder->forCompany($this->company)
        ));

        $customerLegalMentions = implode('<br>', array_map($escape, array_filter([
            $customer->siren ? 'SIREN '.$customer->siren : null,
            $customer->siret ? 'SIRET '.$customer->siret : null,
            $customer->vat_number ? 'TVA intracommunautaire '.$customer->vat_number : null,
            $customer->ape_code ? 'Code APE '.$customer->ape_code : null,
        ])));

        $fields = [
            '{SHIPPING_ADDRESS_NAME}' => $shippingAddress->name,
            '{SHIPPING_COUNTRY}' => $shippingAddress->country_name,
            '{SHIPPING_STATE}' => $shippingAddress->state,
            '{SHIPPING_CITY}' => $shippingAddress->city,
            '{SHIPPING_ADDRESS_STREET_1}' => $shippingAddress->address_street_1,
            '{SHIPPING_ADDRESS_STREET_2}' => $shippingAddress->address_street_2,
            '{SHIPPING_PHONE}' => $shippingAddress->phone,
            '{SHIPPING_ZIP_CODE}' => $shippingAddress->zip,
            '{BILLING_ADDRESS_NAME}' => $billingAddress->name,
            '{BILLING_COUNTRY}' => $billingAddress->country_name,
            '{BILLING_STATE}' => $billingAddress->state,
            '{BILLING_CITY}' => $billingAddress->city,
            '{BILLING_ADDRESS_STREET_1}' => $billingAddress->address_street_1,
            '{BILLING_ADDRESS_STREET_2}' => $billingAddress->address_street_2,
            '{BILLING_PHONE}' => $billingAddress->phone,
            '{BILLING_ZIP_CODE}' => $billingAddress->zip,
            '{COMPANY_NAME}' => $this->company->name,
            '{COMPANY_COUNTRY}' => $companyAddress->country_name,
            '{COMPANY_STATE}' => $companyAddress->state,
            '{COMPANY_CITY}' => $companyAddress->city,
            '{COMPANY_ADDRESS_STREET_1}' => $companyAddress->address_street_1,
            '{COMPANY_ADDRESS_STREET_2}' => $companyAddress->address_street_2,
            '{COMPANY_PHONE}' => $companyAddress->phone,
            '{COMPANY_ZIP_CODE}' => $companyAddress->zip,
            '{COMPANY_LEGAL_FORM}' => $this->company->legal_form,
            '{COMPANY_SIREN}' => $this->company->siren,
            '{COMPANY_SIRET}' => $this->company->siret,
            '{COMPANY_VAT_NUMBER}' => $this->company->vat_number,
            '{COMPANY_APE_CODE}' => $this->company->ape_code,
            '{COMPANY_RCS_CITY}' => $this->company->rcs_city,
            '{COMPANY_LEGAL_MENTIONS}' => $companyLegalMentions,
            '{COMPANY_IBAN}' => $this->company->iban,
            '{COMPANY_BIC}' => $this->company->bic,
            '{CONTACT_DISPLAY_NAME}' => $customer->name,
            '{PRIMARY_CONTACT_NAME}' => $customer->contact_name,
            '{CONTACT_EMAIL}' => $customer->email,
            '{CONTACT_PHONE}' => $customer->phone,
            '{CONTACT_WEBSITE}' => $customer->website,
            '{CUSTOMER_SIREN}' => $customer->siren,
            '{CUSTOMER_SIRET}' => $customer->siret,
            '{CUSTOMER_VAT_NUMBER}' => $customer->vat_number,
            '{CUSTOMER_APE_CODE}' => $customer->ape_code,
            '{CUSTOMER_LEGAL_MENTIONS}' => $customerLegalMentions,
        ];

        foreach ($this->fields as $customField) {
            $fields['{'.$customField->customField->slug.'}'] = $customField->defaultAnswer;
        }

        foreach ($customer->fields as $customField) {
            $fields['{'.$customField->customField->slug.'}'] = $customField->defaultAnswer;
        }

        foreach ($fields as $key => $field) {
            if (in_array($key, ['{COMPANY_LEGAL_MENTIONS}', '{CUSTOMER_LEGAL_MENTIONS}'], true)) {
                continue;
            }

            $fields[$key] = $escape($field);
        }

        return $fields;
    }

    public function getFormattedString($format)
    {
        $values = array_merge($this->getFieldsArray(), $this->getExtraFields());
        $string = nl2br(strtr($format, $values));
        $string = preg_replace('/{(.*?)}/', '', $string);
        $string = preg_replace("/<[^\/>]*>([\s]?)*<\/[^>]*>/", '', $string);
        $string = str_replace('<p>', '', $string);

        return str_replace('</p>', '</br>', $string);
    }
}
