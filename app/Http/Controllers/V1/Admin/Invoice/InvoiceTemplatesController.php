<?php

namespace Crater\Http\Controllers\V1\Admin\Invoice;

use Crater\Http\Controllers\Controller;
use Crater\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceTemplatesController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoiceTemplates = collect(config('document-templates.invoices', []))
            ->map(function (array $template): array {
                $template['path'] = $template['preview']
                    ? asset($template['preview'])
                    : null;
                unset($template['preview']);

                return $template;
            })
            ->values();

        return response()->json([
            'invoiceTemplates' => $invoiceTemplates,
        ]);
    }
}
