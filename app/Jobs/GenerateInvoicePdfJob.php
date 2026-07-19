<?php

namespace Crater\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $invoice;

    public $deleteExistingFile;

    public function __construct($invoice, $deleteExistingFile = false)
    {
        $this->invoice = $invoice;
        $this->deleteExistingFile = $deleteExistingFile;
    }

    public function handle(): int
    {
        $result = $this->invoice->generatePDF(
            'invoice',
            $this->invoice->invoice_number,
            $this->deleteExistingFile
        );

        if (is_string($result) && $result !== '') {
            throw new RuntimeException($result);
        }

        return 0;
    }
}
