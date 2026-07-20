<?php

namespace Crater\Domain\Payments;

use Crater\Models\Company;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Symfony\Component\Process\Process;

final class SepaQrCodeService
{
    public function forInvoice(Invoice $invoice): ?string
    {
        if ($invoice->show_sepa_qr === false || (int) $invoice->show_sepa_qr === 0) {
            return null;
        }

        $amount = (int) ($invoice->due_amount ?? $invoice->total ?? 0);

        return $this->dataUri(
            $invoice->company,
            $amount > 0 ? $amount : null,
            (string) $invoice->invoice_number,
        );
    }

    public function forEstimate(Estimate $estimate): ?string
    {
        if ($estimate->show_sepa_qr === false || (int) $estimate->show_sepa_qr === 0) {
            return null;
        }

        return $this->dataUri(
            $estimate->company,
            null,
            (string) $estimate->estimate_number,
        );
    }

    public function buildPayload(Company $company, ?int $amountInCents, string $reference): ?string
    {
        $iban = $this->compactUpper($company->iban);

        if ($iban === '') {
            return null;
        }

        $bic = $this->compactUpper($company->bic);
        $beneficiary = $this->singleLine((string) $company->name, 70);
        $remittance = $this->singleLine('AutoFacture '.$reference, 140);
        $amount = '';

        if ($amountInCents !== null && $amountInCents > 0) {
            $amount = 'EUR'.number_format($amountInCents / 100, 2, '.', '');
        }

        // European Payments Council QR payload (EPC069-12, version 002).
        return implode("\n", [
            'BCD',
            '002',
            '1',
            'SCT',
            $bic,
            $beneficiary,
            $iban,
            $amount,
            '',
            '',
            $remittance,
            '',
        ]);
    }

    private function dataUri(Company $company, ?int $amountInCents, string $reference): ?string
    {
        $payload = $this->buildPayload($company, $amountInCents, $reference);

        if ($payload === null) {
            return null;
        }

        try {
            $process = new Process([
                'qrencode',
                '-t',
                'SVG',
                '-l',
                'M',
                '-m',
                '1',
                '-o',
                '-',
                $payload,
            ]);
            $process->setTimeout(5);
            $process->run();

            if (! $process->isSuccessful()) {
                report(new \RuntimeException('qrencode failed: '.$process->getErrorOutput()));

                return null;
            }

            $svg = trim($process->getOutput());

            if ($svg === '' || ! str_contains($svg, '<svg')) {
                return null;
            }

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function compactUpper($value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim((string) $value)));
    }

    private function singleLine(string $value, int $maximumLength): string
    {
        $value = trim((string) preg_replace('/[\r\n\t]+/', ' ', $value));
        $value = (string) preg_replace('/\s{2,}/', ' ', $value);

        return mb_substr($value, 0, $maximumLength);
    }
}
