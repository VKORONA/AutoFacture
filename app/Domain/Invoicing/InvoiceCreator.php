<?php

namespace Crater\Domain\Invoicing;

use Crater\Http\Requests\InvoicesRequest;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\Customer;
use Crater\Models\Invoice;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class InvoiceCreator
{
    /**
     * @return array{invoice: Invoice, created: bool}
     */
    public function create(InvoicesRequest $request): array
    {
        $companyId = (int) $request->header('company');
        $clientRequestId = (string) $request->input('client_request_id');

        if ($clientRequestId === '') {
            $clientRequestId = (string) Str::uuid();
            $request->merge(['client_request_id' => $clientRequestId]);
        }

        if ($existing = $this->findExisting($companyId, $clientRequestId)) {
            return ['invoice' => $existing, 'created' => false];
        }

        try {
            return DB::transaction(function () use ($request, $companyId, $clientRequestId): array {
                Company::query()
                    ->whereKey($companyId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($existing = $this->findExisting($companyId, $clientRequestId)) {
                    return ['invoice' => $existing, 'created' => false];
                }

                $customer = Customer::query()
                    ->whereKey((int) $request->input('customer_id'))
                    ->where('company_id', $companyId)
                    ->first();

                if (! $customer) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Le client sélectionné n’appartient pas à l’entreprise active.',
                    ]);
                }

                $this->recalculateTotals($request, $companyId);

                $serial = (new SerialNumberFormatter())
                    ->setModel(new Invoice())
                    ->setCompany($companyId)
                    ->setCustomer($customer->id);

                $request->merge([
                    'invoice_number' => $serial->getNextNumber(),
                ]);

                $invoice = Invoice::createInvoice($request);

                Log::info('Invoice created', [
                    'invoice_id' => $invoice->id,
                    'company_id' => $companyId,
                    'customer_id' => $customer->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_request_id' => $clientRequestId,
                ]);

                return ['invoice' => $invoice, 'created' => true];
            }, 3);
        } catch (Throwable $exception) {
            Log::error('Invoice creation failed', [
                'company_id' => $companyId,
                'customer_id' => $request->input('customer_id'),
                'client_request_id' => $clientRequestId,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function findExisting(int $companyId, string $clientRequestId): ?Invoice
    {
        return Invoice::query()
            ->where('company_id', $companyId)
            ->where('client_request_id', $clientRequestId)
            ->first();
    }

    private function recalculateTotals(InvoicesRequest $request, int $companyId): void
    {
        $items = collect($request->input('items', []));
        $taxes = collect($request->input('taxes', []));
        $taxPerItem = trim((string) (CompanySetting::getSetting('tax_per_item', $companyId) ?? 'NO'));

        $subTotal = $items->sum(
            static fn (array $item): int => (int) round((float) ($item['total'] ?? 0))
        );

        $tax = $taxPerItem === 'YES'
            ? $items->sum(static fn (array $item): int => (int) round((float) ($item['tax'] ?? 0)))
            : $taxes->sum(static fn (array $item): int => (int) round((float) ($item['amount'] ?? 0)));

        $discountValue = (int) round((float) $request->input('discount_val', 0));
        $total = $subTotal - $discountValue + $tax;

        if ($total < 0) {
            throw ValidationException::withMessages([
                'total' => 'Le total calculé de la facture ne peut pas être négatif.',
            ]);
        }

        $request->merge([
            'sub_total' => $subTotal,
            'tax' => $tax,
            'total' => $total,
            'discount_val' => $discountValue,
        ]);
    }
}
