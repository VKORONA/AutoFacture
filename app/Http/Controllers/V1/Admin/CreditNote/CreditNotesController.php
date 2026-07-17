<?php

namespace Crater\Http\Controllers\V1\Admin\CreditNote;

use Crater\Domain\Invoicing\CreditNoteIssuer;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\CreateCreditNoteRequest;
use Crater\Http\Resources\CreditNoteResource;
use Crater\Models\CreditNote;
use Crater\Models\Invoice;
use DomainException;
use Illuminate\Http\Request;

class CreditNotesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CreditNote::class);

        $creditNotes = CreditNote::query()
            ->with(['invoice', 'items'])
            ->when($request->invoice_id, fn ($query, $invoiceId) => $query->where('invoice_id', $invoiceId))
            ->latest('issue_date')
            ->paginate($request->integer('limit', 15));

        return CreditNoteResource::collection($creditNotes);
    }

    public function show(CreditNote $creditNote)
    {
        $this->authorize('view', $creditNote);

        return new CreditNoteResource($creditNote->load(['invoice', 'items']));
    }

    public function store(
        CreateCreditNoteRequest $request,
        Invoice $invoice,
        CreditNoteIssuer $issuer
    ) {
        $this->authorize('create', [CreditNote::class, $invoice]);

        try {
            $creditNote = $issuer->issue(
                $invoice,
                $request->string('reason')->toString(),
                $request->filled('amount') ? $request->integer('amount') : null,
                $request->user()
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'CREDIT_NOTE_REJECTED',
            ], 422);
        }

        return (new CreditNoteResource($creditNote))->response()->setStatusCode(201);
    }
}
