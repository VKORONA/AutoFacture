<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Carbon\Carbon;
use Crater\Domain\FrenchInvoicing\FrenchCompanySetup;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\InvoiceResource;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class ConvertEstimateController extends Controller
{
    public function __invoke(
        Request $request,
        Estimate $estimate,
        Invoice $invoice,
        FrenchCompanySetup $companySetup
    ) {
        $this->authorize('create', Invoice::class);

        $companyId = (int) $request->header('company');
        $company = Company::findOrFail($companyId);
        $companySetup->assertComplete($company);

        $invoice = DB::transaction(function () use ($companyId, $estimate, $invoice): Invoice {
            Company::query()
                ->whereKey($companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedEstimate = Estimate::query()
                ->whereKey($estimate->id)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingInvoice = Invoice::query()
                ->where('company_id', $companyId)
                ->where('source_estimate_id', $lockedEstimate->id)
                ->first();

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $lockedEstimate->load(['items.taxes', 'customer', 'taxes']);

            $invoiceDate = Carbon::now();
            $dueDate = null;

            $dueDateEnabled = CompanySetting::getSetting(
                'invoice_set_due_date_automatically',
                $companyId
            );

            if ($dueDateEnabled === 'YES') {
                $dueDateDays = (int) CompanySetting::getSetting(
                    'invoice_due_date_days',
                    $companyId
                );
                $dueDate = Carbon::now()->addDays($dueDateDays)->format('Y-m-d');
            }

            $serial = (new SerialNumberFormatter)
                ->setModel($invoice)
                ->setCompany($companyId)
                ->setCustomer($lockedEstimate->customer_id)
                ->setNextNumbers();

            $exchangeRate = $lockedEstimate->exchange_rate;
            $invoice = Invoice::create([
                'creator_id' => Auth::id(),
                'source_estimate_id' => $lockedEstimate->id,
                'invoice_date' => $invoiceDate->format('Y-m-d'),
                'due_date' => $dueDate,
                'invoice_number' => $serial->getNextNumber(),
                'sequence_number' => $serial->nextSequenceNumber,
                'customer_sequence_number' => $serial->nextCustomerSequenceNumber,
                'reference_number' => $serial->getNextNumber(),
                'customer_id' => $lockedEstimate->customer_id,
                'company_id' => $companyId,
                'template_name' => $lockedEstimate->getInvoiceTemplateName(),
                'status' => Invoice::STATUS_DRAFT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'sub_total' => $lockedEstimate->sub_total,
                'discount' => $lockedEstimate->discount,
                'discount_type' => $lockedEstimate->discount_type,
                'discount_val' => $lockedEstimate->discount_val,
                'total' => $lockedEstimate->total,
                'due_amount' => $lockedEstimate->total,
                'tax_per_item' => $lockedEstimate->tax_per_item,
                'discount_per_item' => $lockedEstimate->discount_per_item,
                'tax' => $lockedEstimate->tax,
                'notes' => $lockedEstimate->notes,
                'exchange_rate' => $exchangeRate,
                'base_discount_val' => $lockedEstimate->discount_val * $exchangeRate,
                'base_sub_total' => $lockedEstimate->sub_total * $exchangeRate,
                'base_total' => $lockedEstimate->total * $exchangeRate,
                'base_tax' => $lockedEstimate->tax * $exchangeRate,
                'base_due_amount' => $lockedEstimate->total * $exchangeRate,
                'currency_id' => $lockedEstimate->currency_id,
                'sales_tax_type' => $lockedEstimate->sales_tax_type,
                'sales_tax_address_type' => $lockedEstimate->sales_tax_address_type,
            ]);

            $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
            $invoice->save();

            foreach ($lockedEstimate->items as $estimateItem) {
                $itemData = $estimateItem->only([
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
                $itemData['base_price'] = $estimateItem->price * $exchangeRate;
                $itemData['base_discount_val'] = $estimateItem->discount_val * $exchangeRate;
                $itemData['base_tax'] = $estimateItem->tax * $exchangeRate;
                $itemData['base_total'] = $estimateItem->total * $exchangeRate;

                $invoiceItem = $invoice->items()->create($itemData);

                foreach ($estimateItem->taxes as $tax) {
                    if (! $tax->amount) {
                        continue;
                    }

                    $invoiceItem->taxes()->create([
                        'company_id' => $companyId,
                        'tax_type_id' => $tax->tax_type_id,
                        'name' => $tax->name,
                        'percent' => $tax->percent,
                        'amount' => $tax->amount,
                    ]);
                }
            }

            foreach ($lockedEstimate->taxes as $tax) {
                $invoice->taxes()->create([
                    'company_id' => $companyId,
                    'tax_type_id' => $tax->tax_type_id,
                    'name' => $tax->name,
                    'percent' => $tax->percent,
                    'amount' => $tax->amount,
                    'exchange_rate' => $exchangeRate,
                    'base_amount' => $tax->amount * $exchangeRate,
                    'currency_id' => $lockedEstimate->currency_id,
                ]);
            }

            $lockedEstimate->forceFill([
                'converted_invoice_id' => $invoice->id,
                'converted_at' => now(),
                'status' => Estimate::STATUS_ACCEPTED,
            ])->save();

            return $invoice;
        }, 3);

        return new InvoiceResource($invoice->fresh(['items.taxes', 'taxes', 'customer']));
    }
}
