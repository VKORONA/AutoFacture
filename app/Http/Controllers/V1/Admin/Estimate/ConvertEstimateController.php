<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\InvoiceResource;
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
    public function __invoke(Request $request, Estimate $estimate, Invoice $invoice)
    {
        $this->authorize('create', Invoice::class);
        $estimate->load(['items.taxes', 'customer', 'taxes']);

        $invoice = DB::transaction(function () use ($request, $estimate, $invoice): Invoice {
            $invoiceDate = Carbon::now();
            $dueDate = null;
            $companyId = (int) $request->header('company');

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

            $serial = (new SerialNumberFormatter())
                ->setModel($invoice)
                ->setCompany($estimate->company_id)
                ->setCustomer($estimate->customer_id)
                ->setNextNumbers();

            $exchangeRate = $estimate->exchange_rate;
            $invoice = Invoice::create([
                'creator_id' => Auth::id(),
                'invoice_date' => $invoiceDate->format('Y-m-d'),
                'due_date' => $dueDate,
                'invoice_number' => $serial->getNextNumber(),
                'sequence_number' => $serial->nextSequenceNumber,
                'customer_sequence_number' => $serial->nextCustomerSequenceNumber,
                'reference_number' => $serial->getNextNumber(),
                'customer_id' => $estimate->customer_id,
                'company_id' => $companyId,
                'template_name' => $estimate->getInvoiceTemplateName(),
                'status' => Invoice::STATUS_DRAFT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'sub_total' => $estimate->sub_total,
                'discount' => $estimate->discount,
                'discount_type' => $estimate->discount_type,
                'discount_val' => $estimate->discount_val,
                'total' => $estimate->total,
                'due_amount' => $estimate->total,
                'tax_per_item' => $estimate->tax_per_item,
                'discount_per_item' => $estimate->discount_per_item,
                'tax' => $estimate->tax,
                'notes' => $estimate->notes,
                'exchange_rate' => $exchangeRate,
                'base_discount_val' => $estimate->discount_val * $exchangeRate,
                'base_sub_total' => $estimate->sub_total * $exchangeRate,
                'base_total' => $estimate->total * $exchangeRate,
                'base_tax' => $estimate->tax * $exchangeRate,
                'base_due_amount' => $estimate->total * $exchangeRate,
                'currency_id' => $estimate->currency_id,
                'sales_tax_type' => $estimate->sales_tax_type,
                'sales_tax_address_type' => $estimate->sales_tax_address_type,
            ]);

            $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
            $invoice->save();

            foreach ($estimate->items as $estimateItem) {
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

            foreach ($estimate->taxes as $tax) {
                $invoice->taxes()->create([
                    'company_id' => $companyId,
                    'tax_type_id' => $tax->tax_type_id,
                    'name' => $tax->name,
                    'percent' => $tax->percent,
                    'amount' => $tax->amount,
                    'exchange_rate' => $exchangeRate,
                    'base_amount' => $tax->amount * $exchangeRate,
                    'currency_id' => $estimate->currency_id,
                ]);
            }

            $estimate->checkForEstimateConvertAction();

            return $invoice;
        });

        return new InvoiceResource($invoice->fresh(['items.taxes', 'taxes', 'customer']));
    }
}
