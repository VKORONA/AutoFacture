<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\InvoiceResource;
use Crater\Models\Invoice;
use Illuminate\Http\Request;

class FinalizeInvoiceController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice, InvoiceFinalizer $finalizer)
    {
        $this->authorize('update', $invoice);

        return new InvoiceResource($finalizer->finalize($invoice, $request->user()));
    }
}
