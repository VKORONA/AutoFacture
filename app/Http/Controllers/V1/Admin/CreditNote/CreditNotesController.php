<?php

namespace Crater\Http\Controllers\V1\Admin\CreditNote;

use Crater\Domain\Invoicing\CreditNoteIssuer;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\CreateCreditNoteRequest;
use Crater\Http\Requests\CreditNoteIndexRequest;
use Crater\Http\Resources\CreditNoteResource;
use Crater\Models\CreditNote;
use Crater\Models\Invoice;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CreditNotesController extends Controller
{
    public function index(CreditNoteIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CreditNote::class);

        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));
        $orderByField = (string) ($filters['orderByField'] ?? 'issue_date');
        $orderBy = (string) ($filters['orderBy'] ?? 'desc');
        $limit = (int) ($filters['limit'] ?? 15);

        $creditNotes = CreditNote::query()
            ->with(['invoice', 'items', 'customer.currency', 'currency'])
            ->when(
                $filters['invoice_id'] ?? null,
                fn (Builder $query, int $invoiceId): Builder => $query->where('invoice_id', $invoiceId),
            )
            ->when(
                $filters['customer_id'] ?? null,
                fn (Builder $query, int $customerId): Builder => $query->where('customer_id', $customerId),
            )
            ->when(
                $filters['from_date'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('issue_date', '>=', $date),
            )
            ->when(
                $filters['to_date'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('issue_date', '<=', $date),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('credit_note_number', 'like', '%'.$search.'%')
                        ->orWhere('reason', 'like', '%'.$search.'%')
                        ->orWhereHas('invoice', function (Builder $query) use ($search): void {
                            $query->where('invoice_number', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('customer', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('company_name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy($orderByField, $orderBy)
            ->paginate($limit);

        return CreditNoteResource::collection($creditNotes);
    }

    public function show(CreditNote $creditNote): CreditNoteResource
    {
        $this->authorize('view', $creditNote);

        $creditNote->load([
            'invoice',
            'items',
            'customer.currency',
            'currency',
            'company.address',
            'creator',
        ]);

        return new CreditNoteResource($creditNote);
    }

    public function store(
        CreateCreditNoteRequest $request,
        Invoice $invoice,
        CreditNoteIssuer $issuer,
    ): JsonResponse {
        $this->authorize('create', [CreditNote::class, $invoice]);

        $validated = $request->validated();

        try {
            $creditNote = $issuer->issue(
                invoice: $invoice,
                reason: (string) $validated['reason'],
                requestedAmount: isset($validated['amount']) ? (int) $validated['amount'] : null,
                user: $request->user(),
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
