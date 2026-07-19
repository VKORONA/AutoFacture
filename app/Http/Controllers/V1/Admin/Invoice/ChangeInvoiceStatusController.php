<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Http\Controllers\Controller;
use Crater\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChangeInvoiceStatusController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice, InvoiceFinalizer $finalizer)
    {
        $this->authorize('send invoice', $invoice);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Invoice::STATUS_SENT,
                Invoice::STATUS_COMPLETED,
            ])],
        ]);

        $invoice = $finalizer->finalize($invoice, $request->user());

        if ($validated['status'] === Invoice::STATUS_COMPLETED) {
            $invoice->forceFill([
                'status' => Invoice::STATUS_COMPLETED,
                'paid_status' => Invoice::STATUS_PAID,
                'due_amount' => 0,
                'base_due_amount' => 0,
                'overdue' => false,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'finalized_at' => $invoice->finalized_at,
            'immutable_hash' => $invoice->immutable_hash,
        ]);
    }
}
