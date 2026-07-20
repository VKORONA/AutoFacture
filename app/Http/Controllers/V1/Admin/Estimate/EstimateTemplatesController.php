<?php

namespace Crater\Http\Controllers\V1\Admin\Estimate;

use Crater\Http\Controllers\Controller;
use Crater\Models\Estimate;
use Illuminate\Http\Request;

class EstimateTemplatesController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', Estimate::class);

        $estimateTemplates = collect(config('document-templates.estimates', []))
            ->map(function (array $template): array {
                $template['path'] = $template['preview']
                    ? asset($template['preview'])
                    : null;
                unset($template['preview']);

                return $template;
            })
            ->values();

        return response()->json([
            'estimateTemplates' => $estimateTemplates,
        ]);
    }
}
