<?php

namespace Crater\Http\Controllers\V1\Admin\Accounting;

use Crater\Domain\Accounting\AccountingExportService;
use Crater\Http\Controllers\Controller;
use Crater\Models\AccountingExportBatch;
use Crater\Models\AccountingSetting;
use Crater\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccountingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $settings = AccountingSetting::defaults((int) $company->id);
        $exports = AccountingExportBatch::query()
            ->where('company_id', $company->id)
            ->latest()
            ->limit(25)
            ->get();

        return response()->json([
            'data' => [
                'settings' => $this->settingsPayload($settings),
                'profiles' => $this->profiles(),
                'exports' => $exports->map(fn (AccountingExportBatch $batch): array => $this->batchPayload($batch)),
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $company = $this->company($request, ownerOnly: true);
        $validated = $request->validate([
            'accounting_mode' => ['required', Rule::in([
                AccountingSetting::MODE_ACCRUAL,
                AccountingSetting::MODE_CASH,
            ])],
            'export_profile' => ['required', Rule::in(array_keys($this->profiles()))],
            'sales_journal_code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9_-]+$/'],
            'bank_journal_code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9_-]+$/'],
            'customer_control_account' => ['required', 'string', 'max:20'],
            'sales_services_account' => ['required', 'string', 'max:20'],
            'sales_goods_account' => ['required', 'string', 'max:20'],
            'bank_account' => ['required', 'string', 'max:20'],
            'rounding_account' => ['required', 'string', 'max:20'],
            'vat_accounts' => ['required', 'array'],
            'vat_accounts.*' => ['nullable', 'string', 'max:20'],
            'include_payments' => ['required', 'boolean'],
            'include_documents' => ['required', 'boolean'],
            'include_commercial_annexes' => ['required', 'boolean'],
        ]);

        $settings = AccountingSetting::defaults((int) $company->id);
        $settings->fill($validated)->save();

        return response()->json([
            'data' => $this->settingsPayload($settings->fresh()),
        ]);
    }

    public function generate(Request $request, AccountingExportService $service): JsonResponse
    {
        $company = $this->company($request);
        $settings = AccountingSetting::defaults((int) $company->id);
        $validated = $request->validate([
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'profile' => ['required', Rule::in(array_keys($this->profiles()))],
            'include_payments' => ['sometimes', 'boolean'],
            'include_documents' => ['sometimes', 'boolean'],
            'include_commercial_annexes' => ['sometimes', 'boolean'],
        ]);

        $batch = $service->generate(
            company: $company,
            user: $request->user(),
            periodStart: $validated['period_start'],
            periodEnd: $validated['period_end'],
            profile: $validated['profile'],
            includePayments: (bool) ($validated['include_payments'] ?? $settings->include_payments),
            includeDocuments: (bool) ($validated['include_documents'] ?? $settings->include_documents),
            includeCommercialAnnexes: (bool) ($validated['include_commercial_annexes'] ?? $settings->include_commercial_annexes),
        );

        return response()->json([
            'data' => $this->batchPayload($batch),
        ], 201);
    }

    public function download(Request $request, AccountingExportBatch $batch)
    {
        $company = $this->company($request);
        abort_unless((int) $batch->company_id === (int) $company->id, 404);
        abort_unless($batch->status === AccountingExportBatch::STATUS_COMPLETED, 409, 'Ce lot n’est pas disponible.');
        abort_unless($batch->archive_path && Storage::disk($batch->archive_disk)->exists($batch->archive_path), 404);

        return Storage::disk($batch->archive_disk)->download(
            $batch->archive_path,
            basename($batch->archive_path),
            [
                'Content-Type' => 'application/zip',
                'X-Content-Type-Options' => 'nosniff',
                'X-AutoFacture-SHA256' => (string) $batch->archive_sha256,
            ],
        );
    }

    private function company(Request $request, bool $ownerOnly = false): Company
    {
        $company = Company::query()
            ->whereKey($request->header('company'))
            ->whereHas('users', fn ($query) => $query->where('users.id', $request->user()->id))
            ->firstOrFail();

        if ($ownerOnly && (int) $company->owner_id !== (int) $request->user()->id) {
            abort(403, 'Seul le propriétaire de l’entreprise peut modifier la configuration comptable.');
        }

        return $company;
    }

    /** @return array<string, string> */
    private function profiles(): array
    {
        return [
            AccountingSetting::PROFILE_UNIVERSAL => 'Universel CSV',
            AccountingSetting::PROFILE_FEC_COMPATIBLE => 'Journal FEC-compatible',
            AccountingSetting::PROFILE_PENNYLANE => 'Pennylane',
            AccountingSetting::PROFILE_EBP => 'EBP',
            AccountingSetting::PROFILE_SAGE => 'Sage',
            AccountingSetting::PROFILE_CEGID => 'Cegid',
        ];
    }

    /** @return array<string, mixed> */
    private function settingsPayload(AccountingSetting $settings): array
    {
        return [
            'accounting_mode' => $settings->accounting_mode,
            'export_profile' => $settings->export_profile,
            'sales_journal_code' => $settings->sales_journal_code,
            'bank_journal_code' => $settings->bank_journal_code,
            'customer_control_account' => $settings->customer_control_account,
            'sales_services_account' => $settings->sales_services_account,
            'sales_goods_account' => $settings->sales_goods_account,
            'bank_account' => $settings->bank_account,
            'rounding_account' => $settings->rounding_account,
            'vat_accounts' => $settings->vat_accounts ?? [],
            'include_payments' => $settings->include_payments,
            'include_documents' => $settings->include_documents,
            'include_commercial_annexes' => $settings->include_commercial_annexes,
        ];
    }

    /** @return array<string, mixed> */
    private function batchPayload(AccountingExportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'uuid' => $batch->uuid,
            'profile' => $batch->profile,
            'period_start' => $batch->period_start?->toDateString(),
            'period_end' => $batch->period_end?->toDateString(),
            'status' => $batch->status,
            'invoice_count' => $batch->invoice_count,
            'credit_note_count' => $batch->credit_note_count,
            'payment_count' => $batch->payment_count,
            'entry_count' => $batch->entry_count,
            'total_debit' => $batch->total_debit,
            'total_credit' => $batch->total_credit,
            'difference' => $batch->difference,
            'archive_sha256' => $batch->archive_sha256,
            'generated_at' => $batch->generated_at?->toIso8601String(),
            'error_message' => $batch->error_message,
            'download_url' => $batch->status === AccountingExportBatch::STATUS_COMPLETED
                ? '/api/v1/accounting/exports/'.$batch->id.'/download'
                : null,
        ];
    }
}
