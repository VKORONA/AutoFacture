<?php

namespace Crater\Domain\Invoicing;

use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

class InvoiceFinalizer
{
    public function finalize(Invoice $invoice, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $user): Invoice {
            $locked = Invoice::query()
                ->with([
                    'items.taxes',
                    'taxes',
                    'customer.billingAddress',
                    'customer.shippingAddress',
                    'company.address',
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($locked->finalized_at) {
                return $locked;
            }

            $snapshot = $this->snapshot($locked);
            $json = json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );

            $locked->forceFill([
                'status' => $locked->status === Invoice::STATUS_DRAFT
                    ? Invoice::STATUS_SENT
                    : $locked->status,
                'sent' => true,
                'finalized_at' => now(),
                'finalized_by' => $user?->id,
                'immutable_hash' => hash('sha256', $json),
                'finalized_snapshot' => Crypt::encryptString($json),
            ])->save();

            $locked->load([
                'items.taxes',
                'taxes',
                'customer',
                'company',
            ]);

            return $locked;
        }, 3);
    }

    public function verify(Invoice $invoice): bool
    {
        if (! $invoice->finalized_snapshot || ! $invoice->immutable_hash) {
            return false;
        }

        try {
            $json = Crypt::decryptString((string) $invoice->finalized_snapshot);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return hash_equals((string) $invoice->immutable_hash, hash('sha256', $json));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Invoice $invoice): array
    {
        $company = $invoice->company;
        $customer = $invoice->customer;

        if (! $company || ! $customer) {
            throw new LogicException('Les données vendeur ou client de la facture sont incomplètes.');
        }

        /** @var Collection<int, InvoiceItem> $items */
        $items = $invoice->items;
        /** @var Collection<int, Tax> $invoiceTaxes */
        $invoiceTaxes = $invoice->taxes;

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
                'id' => $company->id,
                'name' => $company->name,
                'legal_form' => $company->legal_form,
                'siren' => $company->siren,
                'siret' => $company->siret,
                'vat_number' => $company->vat_number,
                'ape_code' => $company->ape_code,
                'address' => optional($company->address)->only([
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
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'siren' => $customer->siren,
                'siret' => $customer->siret,
                'vat_number' => $customer->vat_number,
                'billing_address' => optional($customer->billingAddress)->only([
                    'name',
                    'address_street_1',
                    'address_street_2',
                    'city',
                    'state',
                    'zip',
                    'country_id',
                ]),
            ],
            'items' => $items->sortBy('id')->values()->map(function (InvoiceItem $item): array {
                /** @var Collection<int, Tax> $taxes */
                $taxes = $item->taxes;

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
                    'taxes' => $taxes->sortBy('id')->values()->map(fn (Tax $tax): array => [
                        'tax_type_id' => $tax->tax_type_id,
                        'name' => $tax->name,
                        'percent' => (string) $tax->percent,
                        'amount' => (int) $tax->amount,
                    ])->all(),
                ];
            })->all(),
            'taxes' => $invoiceTaxes->sortBy('id')->values()->map(fn (Tax $tax): array => [
                'tax_type_id' => $tax->tax_type_id,
                'name' => $tax->name,
                'percent' => (string) $tax->percent,
                'amount' => (int) $tax->amount,
            ])->all(),
        ];
    }
}
