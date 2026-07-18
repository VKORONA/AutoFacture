<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests;
use Crater\Http\Requests\DeleteInvoiceRequest;
use Crater\Http\Resources\InvoiceResource;
use Crater\Jobs\GenerateInvoicePdfJob;
use Crater\Models\Invoice;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $limit = $request->has('limit') ? $request->limit : 10;

        $invoices = Invoice::query()
            ->join('customers', function ($join): void {
                $join->on('customers.id', '=', 'invoices.customer_id')
                    ->on('customers.company_id', '=', 'invoices.company_id');
            })
            ->applyFilters($request->all())
            ->select('invoices.*', 'customers.name')
            ->latest('invoices.created_at')
            ->paginateData($limit);

        return InvoiceResource::collection($invoices)
            ->additional(['meta' => [
                'invoice_total_count' => Invoice::query()->count(),
            ]]);
    }

    public function store(Requests\InvoicesRequest $request, InvoiceFinalizer $finalizer)
    {
        $this->authorize('create', Invoice::class);
        $invoice = Invoice::createInvoice($request);

        if ($request->boolean('invoiceSend')) {
            $invoice = $finalizer->finalize($invoice, $request->user());
            $invoice->send($request->only(['to', 'subject', 'body']));
        }

        GenerateInvoicePdfJob::dispatch($invoice);

        return new InvoiceResource($invoice);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice);
    }

    public function update(Requests\InvoicesRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice = $invoice->updateInvoice($request);

        if (is_string($invoice)) {
            return respondJson($invoice, $invoice);
        }

        GenerateInvoicePdfJob::dispatch($invoice, true);

        return new InvoiceResource($invoice);
    }

    public function delete(DeleteInvoiceRequest $request)
    {
        $this->authorize('delete multiple invoices');

        $lockedNumbers = Invoice::query()
            ->whereIn('id', $request->ids)
            ->whereNotNull('finalized_at')
            ->pluck('invoice_number');

        if ($lockedNumbers->isNotEmpty()) {
            return response()->json([
                'message' => 'Les factures finalisées ne peuvent pas être supprimées. Créez un avoir.',
                'code' => 'FINALIZED_INVOICE_LOCKED',
                'invoices' => $lockedNumbers,
            ], 422);
        }

        Invoice::deleteInvoices($request->ids);

        return response()->json(['success' => true]);
    }
}
