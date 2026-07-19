<?php

namespace Crater\Domain\Accounting;

use Barryvdh\DomPDF\Facade\Pdf;
use Crater\Models\AccountingExportBatch;
use Crater\Models\AccountingSetting;
use Crater\Models\Company;
use Crater\Models\CreditNote;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Crater\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class AccountingExportService
{
    public function __construct(
        private readonly AccountingEntryBuilder $entryBuilder,
    ) {}

    public function generate(
        Company $company,
        User $user,
        string $periodStart,
        string $periodEnd,
        string $profile,
        bool $includePayments,
        bool $includeDocuments,
        bool $includeCommercialAnnexes,
    ): AccountingExportBatch {
        $settings = AccountingSetting::defaults((int) $company->id);
        $uuid = (string) Str::uuid();
        $batch = AccountingExportBatch::create([
            'uuid' => $uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'profile' => $profile,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => AccountingExportBatch::STATUS_PROCESSING,
            'include_payments' => $includePayments,
            'include_documents' => $includeDocuments,
            'include_commercial_annexes' => $includeCommercialAnnexes,
        ]);

        $directory = 'accounting-exports/'.$company->id.'/'.$uuid;
        $archivePath = $directory.'/AutoFacture-export-comptable-'.$periodStart.'-'.$periodEnd.'.zip';

        try {
            $result = $this->entryBuilder->build(
                (int) $company->id,
                $periodStart,
                $periodEnd,
                $settings,
                $includePayments,
            );

            if ($result['totals']['difference'] !== 0) {
                throw new RuntimeException('Le lot comptable est déséquilibré et ne peut pas être exporté.');
            }

            Storage::disk('local')->makeDirectory($directory.'/work');
            $workPath = Storage::disk('local')->path($directory.'/work');
            $files = [];
            $warnings = [];

            $files[] = $this->writeUniversalCsv($workPath, $result['entries']);
            $files[] = $this->writeFecCompatible($workPath, $result['entries']);

            if (! in_array($profile, [AccountingSetting::PROFILE_UNIVERSAL, AccountingSetting::PROFILE_FEC_COMPATIBLE], true)) {
                $files[] = $this->writeProfileCsv($workPath, $result['entries'], $profile);
            }

            $files[] = $this->writePaymentsCsv($workPath, $result['payments']);
            $files[] = $this->writeCustomersCsv($workPath, $result['invoices'], $result['credit_notes']);

            if ($includeDocuments) {
                $warnings = array_merge(
                    $warnings,
                    $this->addOfficialDocuments(
                        $workPath,
                        $result['invoices'],
                        $result['credit_notes'],
                        $includePayments ? $result['payments'] : collect(),
                    ),
                );
            }

            if ($includeCommercialAnnexes) {
                $warnings = array_merge(
                    $warnings,
                    $this->addCommercialAnnexes($workPath, $result['invoices']),
                );
            }

            $manifest = $this->manifest(
                company: $company,
                batch: $batch,
                settings: $settings,
                result: $result,
                workPath: $workPath,
                warnings: $warnings,
            );
            $manifestPath = $workPath.'/manifest.json';
            file_put_contents(
                $manifestPath,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            );
            $files[] = $manifestPath;

            $reportPath = $workPath.'/rapport-controle.pdf';
            file_put_contents($reportPath, Pdf::loadView('app.pdf.accounting-export-report', [
                'company' => $company,
                'batch' => $batch,
                'manifest' => $manifest,
            ])->setPaper('a4')->output());
            $files[] = $reportPath;

            $this->createZip($workPath, Storage::disk('local')->path($archivePath));
            $archiveHash = hash_file('sha256', Storage::disk('local')->path($archivePath));

            $batch->forceFill([
                'status' => AccountingExportBatch::STATUS_COMPLETED,
                'invoice_count' => $result['invoices']->count(),
                'credit_note_count' => $result['credit_notes']->count(),
                'payment_count' => $result['payments']->count(),
                'entry_count' => count($result['entries']),
                'total_debit' => $result['totals']['debit'],
                'total_credit' => $result['totals']['credit'],
                'difference' => $result['totals']['difference'],
                'archive_path' => $archivePath,
                'archive_sha256' => $archiveHash,
                'manifest' => $manifest,
                'generated_at' => now(),
            ])->save();

            Storage::disk('local')->deleteDirectory($directory.'/work');

            return $batch->fresh();
        } catch (Throwable $exception) {
            report($exception);
            $batch->forceFill([
                'status' => AccountingExportBatch::STATUS_FAILED,
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();

            Storage::disk('local')->deleteDirectory($directory.'/work');

            throw $exception;
        }
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function writeUniversalCsv(string $workPath, array $entries): string
    {
        $path = $workPath.'/ecritures-universelles.csv';
        $headers = [
            'Date', 'Journal', 'NumeroEcriture', 'NumeroPiece', 'TypeDocument',
            'CompteGeneral', 'CompteAuxiliaire', 'Client', 'Libelle', 'Debit', 'Credit',
            'TauxTVA', 'MontantHT', 'MontantTVA', 'MontantTTC', 'DateEcheance',
            'DatePaiement', 'Devise', 'NomFichierJustificatif',
        ];
        $rows = array_map(fn (array $entry): array => [
            $this->displayDate($entry['entry_date']),
            $entry['journal_code'],
            $entry['entry_number'],
            $entry['piece_reference'],
            $entry['source_type'],
            $entry['account_number'],
            $entry['auxiliary_number'],
            $entry['auxiliary_label'],
            $entry['label'],
            $this->money($entry['debit']),
            $this->money($entry['credit']),
            $entry['vat_rate'],
            $this->money($entry['amount_ht']),
            $this->money($entry['amount_vat']),
            $this->money($entry['amount_ttc']),
            $this->displayDate($entry['due_date']),
            $this->displayDate($entry['payment_date']),
            $entry['currency_code'],
            $entry['document_filename'],
        ], $entries);

        $this->writeDelimited($path, $headers, $rows, ';', true);

        return $path;
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function writeFecCompatible(string $workPath, array $entries): string
    {
        $path = $workPath.'/journal-ventes-fec-compatible.txt';
        $headers = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate', 'CompteNum',
            'CompteLib', 'CompAuxNum', 'CompAuxLib', 'PieceRef', 'PieceDate',
            'EcritureLib', 'Debit', 'Credit', 'EcritureLet', 'DateLet', 'ValidDate',
            'Montantdevise', 'Idevise',
        ];
        $rows = array_map(fn (array $entry): array => [
            $entry['journal_code'],
            $entry['journal_label'],
            $entry['entry_number'],
            $entry['entry_date'],
            $entry['account_number'],
            $entry['account_label'],
            $entry['auxiliary_number'],
            $entry['auxiliary_label'],
            $entry['piece_reference'],
            $entry['piece_date'],
            $entry['label'],
            $this->money($entry['debit']),
            $this->money($entry['credit']),
            $entry['lettering'],
            $entry['lettering_date'],
            $entry['validation_date'],
            $this->money($entry['currency_amount']),
            $entry['currency_code'],
        ], $entries);

        $this->writeDelimited($path, $headers, $rows, "\t", false);

        return $path;
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function writeProfileCsv(string $workPath, array $entries, string $profile): string
    {
        $safeProfile = preg_replace('/[^a-z0-9_-]+/i', '-', $profile) ?: 'personnalise';
        $path = $workPath.'/ecritures-'.$safeProfile.'.csv';
        $headers = [
            'Date', 'Journal', 'PieceRef', 'CompteNum', 'CompAuxNum', 'Libelle',
            'Debit', 'Credit', 'Devise', 'Justificatif',
        ];
        $rows = array_map(fn (array $entry): array => [
            $this->displayDate($entry['entry_date']),
            $entry['journal_code'],
            $entry['piece_reference'],
            $entry['account_number'],
            $entry['auxiliary_number'],
            $entry['label'],
            $this->money($entry['debit']),
            $this->money($entry['credit']),
            $entry['currency_code'],
            $entry['document_filename'],
        ], $entries);

        $this->writeDelimited($path, $headers, $rows, ';', true);

        return $path;
    }

    private function writePaymentsCsv(string $workPath, $payments): string
    {
        $path = $workPath.'/reglements.csv';
        $headers = ['Date', 'Numero', 'Client', 'Facture', 'Mode', 'Montant', 'Devise'];
        $rows = $payments->map(fn (Payment $payment): array => [
            $this->displayDate($this->rawDate($payment, 'payment_date')),
            (string) ($payment->payment_number ?: 'REG-'.$payment->id),
            trim((string) ($payment->customer?->company_name ?: $payment->customer?->name)),
            (string) ($payment->invoice?->invoice_number ?? ''),
            (string) ($payment->paymentMethod?->name ?? $payment->payment_method_id ?? ''),
            $this->money((int) $payment->amount),
            (string) ($payment->currency?->code ?? 'EUR'),
        ])->all();

        $this->writeDelimited($path, $headers, $rows, ';', true);

        return $path;
    }

    private function writeCustomersCsv(string $workPath, $invoices, $creditNotes): string
    {
        $path = $workPath.'/clients.csv';
        $customers = $invoices->pluck('customer')
            ->concat($creditNotes->pluck('customer'))
            ->filter()
            ->unique('id')
            ->sortBy('id');
        $headers = ['CompteAuxiliaire', 'Nom', 'RaisonSociale', 'Email', 'SIREN', 'SIRET', 'TVAIntracommunautaire'];
        $rows = $customers->map(fn ($customer): array => [
            'C'.str_pad((string) $customer->id, 8, '0', STR_PAD_LEFT),
            (string) $customer->name,
            (string) $customer->company_name,
            (string) $customer->email,
            (string) $customer->siren,
            (string) $customer->siret,
            (string) $customer->vat_number,
        ])->all();

        $this->writeDelimited($path, $headers, $rows, ';', true);

        return $path;
    }

    private function addOfficialDocuments(string $workPath, $invoices, $creditNotes, $payments): array
    {
        $warnings = [];

        foreach ($invoices as $invoice) {
            try {
                $directory = $workPath.'/Factures';
                $this->ensureDirectory($directory);
                file_put_contents($directory.'/'.$this->safeFilename($invoice->invoice_number).'.pdf', $invoice->getPDFData()->output());
            } catch (Throwable $exception) {
                report($exception);
                $warnings[] = 'PDF facture '.$invoice->invoice_number.' non ajouté : '.$exception->getMessage();
            }
        }

        foreach ($creditNotes as $creditNote) {
            try {
                $directory = $workPath.'/Avoirs';
                $this->ensureDirectory($directory);
                $document = json_decode(
                    Crypt::decryptString((string) $creditNote->finalized_snapshot),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
                $pdf = Pdf::loadView('app.pdf.credit-note.default', ['document' => $document])->setPaper('a4');
                file_put_contents($directory.'/'.$this->safeFilename($creditNote->credit_note_number).'.pdf', $pdf->output());
            } catch (Throwable $exception) {
                report($exception);
                $warnings[] = 'PDF avoir '.$creditNote->credit_note_number.' non ajouté : '.$exception->getMessage();
            }
        }

        foreach ($payments as $payment) {
            try {
                $directory = $workPath.'/Reglements';
                $this->ensureDirectory($directory);
                $number = (string) ($payment->payment_number ?: 'REG-'.$payment->id);
                file_put_contents($directory.'/'.$this->safeFilename($number).'.pdf', $payment->getPDFData()->output());
            } catch (Throwable $exception) {
                report($exception);
                $warnings[] = 'Reçu de règlement '.$payment->id.' non ajouté : '.$exception->getMessage();
            }
        }

        return $warnings;
    }

    private function addCommercialAnnexes(string $workPath, $invoices): array
    {
        $warnings = [];
        $estimates = Estimate::query()
            ->whereIn('converted_invoice_id', $invoices->pluck('id'))
            ->with(['attachments', 'linePhotos'])
            ->get();

        foreach ($estimates as $estimate) {
            $directory = $workPath.'/Annexes/'.$this->safeFilename($estimate->estimate_number);
            $this->ensureDirectory($directory);

            foreach ($estimate->linePhotos as $photo) {
                try {
                    $path = $photo->image_path;
                    if (Storage::disk($photo->disk)->exists($path)) {
                        file_put_contents(
                            $directory.'/ligne-'.$photo->line_uuid.'-photo-'.str_pad((string) ($photo->sort_order + 1), 2, '0', STR_PAD_LEFT).'.webp',
                            Storage::disk($photo->disk)->get($path),
                        );
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $warnings[] = 'Photo du devis '.$estimate->estimate_number.' non ajoutée.';
                }
            }

            foreach ($estimate->attachments as $attachment) {
                try {
                    if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                        file_put_contents(
                            $directory.'/'.$this->safeFilename($attachment->original_name),
                            Storage::disk($attachment->disk)->get($attachment->path),
                        );
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $warnings[] = 'Annexe du devis '.$estimate->estimate_number.' non ajoutée.';
                }
            }
        }

        return $warnings;
    }

    private function manifest(
        Company $company,
        AccountingExportBatch $batch,
        AccountingSetting $settings,
        array $result,
        string $workPath,
        array $warnings,
    ): array {
        $fileHashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workPath, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($workPath) + 1));
            $fileHashes[$relative] = hash_file('sha256', $file->getPathname());
        }

        ksort($fileHashes);

        return [
            'schema' => 'autofacture.accounting-export.v1',
            'notice' => 'Le fichier journal-ventes-fec-compatible.txt est un export de ventes et règlements. Il ne constitue pas un FEC réglementaire complet tant qu’AutoFacture ne tient pas toute la comptabilité de l’entreprise.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'siren' => $company->siren,
                'siret' => $company->siret,
            ],
            'export' => [
                'uuid' => $batch->uuid,
                'created_at' => now()->toIso8601String(),
                'period_start' => $batch->period_start->toDateString(),
                'period_end' => $batch->period_end->toDateString(),
                'profile' => $batch->profile,
                'accounting_mode' => $settings->accounting_mode,
                'include_payments' => $batch->include_payments,
                'include_documents' => $batch->include_documents,
                'include_commercial_annexes' => $batch->include_commercial_annexes,
                'factur_x_included' => false,
            ],
            'counts' => [
                'invoices' => $result['invoices']->count(),
                'credit_notes' => $result['credit_notes']->count(),
                'payments' => $result['payments']->count(),
                'entries' => count($result['entries']),
            ],
            'control' => [
                'total_debit_cents' => $result['totals']['debit'],
                'total_credit_cents' => $result['totals']['credit'],
                'difference_cents' => $result['totals']['difference'],
                'balanced' => $result['totals']['difference'] === 0,
            ],
            'warnings' => array_values($warnings),
            'files' => $fileHashes,
        ];
    }

    private function createZip(string $sourceDirectory, string $archivePath): void
    {
        $this->ensureDirectory(dirname($archivePath));
        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer l’archive comptable.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirectory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDirectory) + 1));
                $zip->addFile($file->getPathname(), $relative);
            }
        }

        $zip->close();
    }

    private function writeDelimited(string $path, array $headers, array $rows, string $delimiter, bool $bom): void
    {
        $handle = fopen($path, 'wb');

        if (! $handle) {
            throw new RuntimeException('Impossible de créer le fichier comptable '.$path.'.');
        }

        if ($bom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv($handle, $headers, $delimiter, '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($value) => $value ?? '', $row), $delimiter, '"', '');
        }
        fclose($handle);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }

    private function displayDate(?string $ymd): string
    {
        if (! $ymd || strlen($ymd) !== 8) {
            return '';
        }

        return substr($ymd, 6, 2).'/'.substr($ymd, 4, 2).'/'.substr($ymd, 0, 4);
    }

    private function rawDate($model, string $field): string
    {
        $value = $model->getRawOriginal($field) ?: $model->{$field};

        return $value ? date('Ymd', strtotime((string) $value)) : '';
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/u', '-', trim($name)) ?: 'document';

        return trim($name, '.-');
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le dossier '.$directory.'.');
        }
    }
}
