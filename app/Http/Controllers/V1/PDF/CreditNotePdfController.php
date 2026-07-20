<?php

namespace Crater\Http\Controllers\V1\PDF;

use Barryvdh\DomPDF\Facade\Pdf;
use Crater\Http\Controllers\Controller;
use Crater\Models\CompanySetting;
use Crater\Models\CreditNote;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class CreditNotePdfController extends Controller
{
    public function __invoke(CreditNote $creditNote): Response
    {
        $this->authorize('view', $creditNote);

        try {
            $json = Crypt::decryptString((string) $creditNote->finalized_snapshot);
            $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            report($exception);
            abort(409, 'Le document scellé de cet avoir ne peut pas être lu.');
        }

        $templateName = CompanySetting::getSetting(
            'default_credit_note_template',
            $creditNote->company_id,
        ) ?: 'premium';

        if (! in_array($templateName, config('document-templates.credit_notes', []), true)) {
            $templateName = 'premium';
        }

        $pdf = Pdf::loadView('app.pdf.credit-note.default', [
            'document' => $document,
            'templateName' => $templateName,
        ])->setPaper('a4');

        return $pdf->stream($creditNote->credit_note_number.'.pdf');
    }
}
