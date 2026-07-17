<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\InvoiceResource;
use Crater\Models\CompanySetting;
use Crater\Models\Invoice;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class CloneInvoiceController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice)
    {
        $this->authorize('create', Invoice::class);
        $invoice->load(['items.taxes', 'taxes', 'fields']);

        $newInvoice = DB::transaction(function () use ($request, $invoice): Invoice {
            $companyId = (int) $request->header('company');
            $date = Carbon::now();
            $dueDate = null;

            $serial = (new SerialNumberFormatter())
                ->setModel(new Invoice())
                ->setCompany($invoice->company_id)
                ->setCustomer($invoice->customer_id)
                ->setNextNumbers();

            if (CompanySetting::getSetting('invoice_set_due_date_automatically', $companyId) === 'YES') {
                $dueDateDays = (int) CompanySetting::getSetting('invoice_due_date_days', $companyId);
                $dueDate = Carbon::now()->addDays($dueDateDays)->format('Y-m-d');
            }

            $exchangeRate = $invoice->exchange_rate;
            $newInvoice = Invoice::create([
                'creator_id' => $request->user()->id,
                'invoice_date' => $date->format('Y-m-d'),
                'due_date' => $dueDate,
                'invoice_number' => $serial->getNextNumber(),
                'sequence_number' => $serial->nextSequenceNumber,
                'customer_sequence_number' => $serial->nextCustomerSequenceNumber,
                'reference_number' => $invoice->reference_number,
                'customer_id' => $invoice->customer_id,
                'company_id' => $companyId,
                'template_name' => $invoice->template_name,
                'status' => Invoice::STATUS_DRAFT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'sub_total' => $invoice->sub_total,
                'discount' => $invoice->discount,
                'discount_type' => $invoice->discount_type,
                'discount_val' => $invoice->discount_val,
                'total' => $invoice->total,
                'due_amount' => $invoice->total,
                'tax_per_item' => $invoice->tax_per_item,
                'discount_per_item' => $invoice->discount_per_item,
                'tax' => $invoice->tax,
                'notes' => $invoice->notes,
                'exchange_rate' => $exchangeRate,
                'base_total' => $invoice->total * $exchangeRate,
                'base_discount_val' => $invoice->discount_val * $exchangeRate,
                'base_sub_total' => $invoice->sub_total * $exchangeRate,
                'base_tax' => $invoice->tax * $exchangeRate,
                'base_due_amount' => $invoice->total * $exchangeRate,
                'currency_id' => $invoice->currency_id,
                'sales_tax_type' => $invoice->sales_tax_type,
                'sales_tax_address_type' => $invoice->sales_tax_address_type,
            ]);

            $newInvoice->unique_hash = Hashids::connection(Invoice::class)->encode($newInvoice->id);
            $newInvoice->save();

            foreach ($invoice->items as $sourceItem) {
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

                $newItem = $newInvoice->items()->create($itemData);

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

            foreach ($invoice->taxes as $tax) {
                $newInvoice->taxes()->create([
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

            if ($invoice->fields->isNotEmpty()) {
                $newInvoice->addCustomFields($invoice->fields->map(fn ($field) => [
                    'id' => $field->custom_field_id,
                    'value' => $field->defaultAnswer,
                ])->all());
            }

            return $newInvoice;
        });

        return new InvoiceResource($newInvoice->fresh(['items.taxes', 'taxes', 'customer']));
    }
}
