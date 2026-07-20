<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\EstimateResource;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\Estimate;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class CloneEstimateController extends Controller
{
    public function __invoke(Request $request, Estimate $estimate)
    {
        $this->authorize('create', Estimate::class);
        $this->authorize('view', $estimate);

        $estimate->load(['items.taxes', 'taxes', 'fields']);

        $newEstimate = DB::transaction(function () use ($request, $estimate): Estimate {
            $companyId = (int) $request->header('company');

            Company::query()
                ->whereKey($companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $date = Carbon::now();
            $expiryDate = null;

            if (CompanySetting::getSetting('estimate_set_expiry_date_automatically', $companyId) === 'YES') {
                $expiryDays = (int) CompanySetting::getSetting('estimate_expiry_date_days', $companyId);
                $expiryDate = Carbon::now()->addDays($expiryDays)->format('Y-m-d');
            }

            $serial = (new SerialNumberFormatter)
                ->setModel(new Estimate)
                ->setCompany($companyId)
                ->setCustomer($estimate->customer_id)
                ->setNextNumbers();

            $exchangeRate = $estimate->exchange_rate;

            $newEstimate = Estimate::create([
                'creator_id' => $request->user()->id,
                'estimate_date' => $date->format('Y-m-d'),
                'expiry_date' => $expiryDate,
                'estimate_number' => $serial->getNextNumber(),
                'sequence_number' => $serial->nextSequenceNumber,
                'customer_sequence_number' => $serial->nextCustomerSequenceNumber,
                'reference_number' => $estimate->reference_number,
                'project_name' => $estimate->project_name,
                'project_address' => $estimate->project_address,
                'purchase_order_number' => $estimate->purchase_order_number,
                'project_contact' => $estimate->project_contact,
                'payment_terms_label' => $estimate->payment_terms_label,
                'show_sepa_qr' => (bool) $estimate->show_sepa_qr,
                'customer_id' => $estimate->customer_id,
                'company_id' => $companyId,
                'template_name' => $estimate->template_name,
                'status' => Estimate::STATUS_DRAFT,
                'sub_total' => $estimate->sub_total,
                'discount' => $estimate->discount,
                'discount_type' => $estimate->discount_type,
                'discount_val' => $estimate->discount_val,
                'total' => $estimate->total,
                'tax_per_item' => $estimate->tax_per_item,
                'discount_per_item' => $estimate->discount_per_item,
                'tax' => $estimate->tax,
                'notes' => $estimate->notes,
                'annex_title' => $estimate->annex_title,
                'annex_notes' => $estimate->annex_notes,
                // Les fichiers et photos ne sont pas dupliqués afin d’éviter toute pièce jointe obsolète.
                'include_photo_annex' => false,
                'exchange_rate' => $exchangeRate,
                'base_total' => $estimate->total * $exchangeRate,
                'base_discount_val' => $estimate->discount_val * $exchangeRate,
                'base_sub_total' => $estimate->sub_total * $exchangeRate,
                'base_tax' => $estimate->tax * $exchangeRate,
                'currency_id' => $estimate->currency_id,
                'sales_tax_type' => $estimate->sales_tax_type,
                'sales_tax_address_type' => $estimate->sales_tax_address_type,
            ]);

            $newEstimate->unique_hash = Hashids::connection(Estimate::class)->encode($newEstimate->id);
            $newEstimate->save();

            foreach ($estimate->items as $sourceItem) {
                $itemData = $sourceItem->only([
                    'item_id',
                    'name',
                    'description',
                    'quantity',
                    'price',
                    'discount',
                    'discount_type',
                    'discount_val',
                    'tax',
                    'total',
                    'unit_name',
                ]);
                $itemData['company_id'] = $companyId;
                $itemData['exchange_rate'] = $exchangeRate;
                $itemData['base_price'] = $sourceItem->price * $exchangeRate;
                $itemData['base_discount_val'] = $sourceItem->discount_val * $exchangeRate;
                $itemData['base_tax'] = $sourceItem->tax * $exchangeRate;
                $itemData['base_total'] = $sourceItem->total * $exchangeRate;

                $newItem = $newEstimate->items()->create($itemData);

                foreach ($sourceItem->taxes as $tax) {
                    if (! $tax->amount) {
                        continue;
                    }

                    $newItem->taxes()->create([
                        'company_id' => $companyId,
                        'tax_type_id' => $tax->tax_type_id,
                        'name' => $tax->name,
                        'percent' => $tax->percent,
                        'amount' => $tax->amount,
                    ]);
                }
            }

            foreach ($estimate->taxes as $tax) {
                $newEstimate->taxes()->create([
                    'company_id' => $companyId,
                    'tax_type_id' => $tax->tax_type_id,
                    'name' => $tax->name,
                    'percent' => $tax->percent,
                    'amount' => $tax->amount,
                    'exchange_rate' => $tax->exchange_rate,
                    'base_amount' => $tax->base_amount,
                    'currency_id' => $tax->currency_id,
                ]);
            }

            if ($estimate->fields->isNotEmpty()) {
                $newEstimate->addCustomFields($estimate->fields->map(fn ($field) => [
                    'id' => $field->custom_field_id,
                    'value' => $field->defaultAnswer,
                ])->all());
            }

            return $newEstimate;
        });

        return (new EstimateResource($newEstimate->fresh(['items.taxes', 'taxes', 'customer'])))
            ->additional([
                'meta' => [
                    'duplicated_from' => $estimate->id,
                    'attachments_copied' => false,
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }
}
