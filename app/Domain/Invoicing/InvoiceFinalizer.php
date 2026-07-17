<?php

namespace Crater\Domain\Invoicing;

use Crater\Models\Invoice;
use Crater\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class InvoiceFinalizer
{
    public function finalize(Invoice $invoice, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $user): Invoice {
            $locked = Invoice::query()
                ->with(['items.taxes', 'taxes', 'customer.billingAddress', 'customer.shippingAddress', 'company.address'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($locked->finalized_at) {
                return $locked;
            }

            $snapshot = $this->snapshot($locked);
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            $locked->forceFill([
                'status' => $locked->status === Invoice::STATUS_DRAFT ? Invoice::STATUS_SENT : $locked->status,
                'sent' => true,
                'finalized_at' => now(),
                'finalized_by' => $user?->id,
                'immutable_hash' => hash('sha256', $json),
                'finalized_snapshot' => Crypt::encryptString($json),
            ])->save();

            return $locked->fresh([
                'items.taxes',
                'taxes',
                'customer',
                'company',
            ]);
        }, 3);
    }

    public function verify(Invoice $invoice): bool
    {
        if (! $invoice->finalized_snapshot || ! $invoice->immutable_hash) {
            return false;
        }

        $json = Crypt::decryptString($invoice->finalized_snapshot);

        return hash_equals($invoice->immutable_hash, hash('sha256', $json));
    }

    private function snapshot(Invoice $invoice): array
    {
        return [
            'schema' => 'autofacture.invoice.snapshot.v1',
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number,
                'reference' => $invoice->reference_number,
                'date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => (string) $invoice->exchange_rate,
                'sub_total' => (int) $invoice->sub_total,
                'discount' => (string) $invoice->discount,
                'discount_val' => (int) $invoice->discount_val,
                'tax' => (int) $invoice->tax,
                'total' => (int) $invoice->total,
                'notes' => $invoice->notes,
                'tax_per_item' => $invoice->tax_per_item,
                'discount_per_item' => $invoice->discount_per_item,
            ],
            'seller' => [
                'id' => $invoice->company->id,
                'name' => $invoice->company->name,
                'legal_form' => $invoice->company->legal_form,
                'siren' => $invoice->company->siren,
                'siret' => $invoice->company->siret,
                'vat_number' => $invoice->company->vat_number,
                'ape_code' => $invoice->company->ape_code,
                'address' => optional($invoice->company->address)->only([
                    'name',
                    'address_street_1',
                    'address_street_2',
                    'city',
                    'state',
                    'zip',
                    'country_id',
                ]),
            ],
            'buyer' => [
                'id' => $invoice->customer->id,
                'name' => $invoice->customer->name,
                'company_name' => $invoice->customer->company_name,
                'siren' => $invoice->customer->siren,
                'siret' => $invoice->customer->siret,
                'vat_number' => $invoice->customer->vat_number,
                'billing_address' => optional($invoice->customer->billingAddress)->only([
                    'name',
                    'address_street_1',
                    'address_street_2',
                    'city',
                    'state',
                    'zip',
                    'country_id',
                ]),
            ],
            'items' => $invoice->items->sortBy('id')->values()->map(function ($item): array {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => (string) $item->quantity,
                    'price' => (int) $item->price,
                    'discount' => (string) $item->discount,
                    'discount_val' => (int) $item->discount_val,
                    'tax' => (int) $item->tax,
                    'total' => (int) $item->total,
                    'taxes' => $item->taxes->sortBy('id')->values()->map(fn ($tax) => [
                        'tax_type_id' => $tax->tax_type_id,
                        'name' => $tax->name,
                        'percent' => (string) $tax->percent,
                        'amount' => (int) $tax->amount,
                    ])->all(),
                ];
            })->all(),
            'taxes' => $invoice->taxes->sortBy('id')->values()->map(fn ($tax) => [
                'tax_type_id' => $tax->tax_type_id,
                'name' => $tax->name,
                'percent' => (string) $tax->percent,
                'amount' => (int) $tax->amount,
            ])->all(),
        ];
    }
}
