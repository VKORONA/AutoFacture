<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Domain\Invoicing\InvoiceCreator;
use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests;
use Crater\Http\Requests\DeleteInvoiceRequest;
use Crater\Http\Resources\InvoiceResource;
use Crater\Jobs\GenerateInvoicePdfJob;
use Crater\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    public function store(
        Requests\InvoicesRequest $request,
        InvoiceCreator $creator,
        InvoiceFinalizer $finalizer
    ): JsonResponse {
        $this->authorize('create', Invoice::class);

        $result = $creator->create($request);
        $invoice = $result['invoice'];
        $created = $result['created'];

        $deliveryStatus = $request->boolean('invoiceSend') ? 'pending' : 'not_requested';
        $deliveryError = null;

        if ($request->boolean('invoiceSend') && $created) {
            try {
                $invoice = $finalizer->finalize($invoice, $request->user());
                $invoice->send($request->only(['to', 'subject', 'body']));
                $deliveryStatus = 'sent';
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Invoice created but email delivery failed', [
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'exception' => $exception::class,
                ]);
                $deliveryStatus = 'failed';
                $deliveryError = 'La facture a été créée, mais son envoi a échoué.';
            }
        } elseif ($request->boolean('invoiceSend')) {
            $deliveryStatus = 'already_processed';
        }

        $pdfStatus = $created ? 'queued' : 'not_regenerated';
        $pdfError = null;

        if ($created) {
            try {
                GenerateInvoicePdfJob::dispatch($invoice);
                $pdfStatus = config('queue.default') === 'sync' ? 'generated' : 'queued';
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Invoice created but PDF generation failed', [
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'exception' => $exception::class,
                ]);
                $pdfStatus = 'failed';
                $pdfError = 'La facture a été enregistrée, mais le PDF n’a pas pu être généré.';
            }
        }

        $invoice = Invoice::query()
            ->with([
                'items',
                'items.fields',
                'items.fields.customField',
                'customer',
                'taxes',
                'company',
                'currency',
            ])
            ->findOrFail($invoice->id);

        $payload = (new InvoiceResource($invoice))->resolve($request);

        return response()->json([
            'data' => $payload,
            // Compatibilité avec l’ancien store Vue qui lisait response.data.invoice.
            'invoice' => $payload,
            'meta' => [
                'created' => $created,
                'pdf_status' => $pdfStatus,
                'pdf_error' => $pdfError,
                'delivery_status' => $deliveryStatus,
                'delivery_error' => $deliveryError,
            ],
        ]);
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
