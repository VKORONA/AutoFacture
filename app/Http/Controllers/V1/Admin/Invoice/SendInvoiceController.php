<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\SendInvoiceRequest;
use Crater\Models\Invoice;

class SendInvoiceController extends Controller
{
    public function __invoke(SendInvoiceRequest $request, Invoice $invoice, InvoiceFinalizer $finalizer)
    {
        $this->authorize('send invoice', $invoice);

        $invoice = $finalizer->finalize($invoice, $request->user());
        $invoice->send($request->all());

        return response()->json([
            'success' => true,
            'finalized_at' => $invoice->finalized_at,
            'immutable_hash' => $invoice->immutable_hash,
        ]);
    }
}
