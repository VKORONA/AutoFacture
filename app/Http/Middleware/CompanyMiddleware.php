<?php

namespace Crater\Http\Middleware;

use Closure;
use Crater\Tenancy\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompanyMiddleware
{
    public function __construct(private CompanyContext $context)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! Schema::hasTable('user_company') || ! $request->user()) {
            return $next($request);
        }

        $companyIds = $request->user()->companies()->pluck('companies.id');
        $requestedCompany = $request->header('company');

        if (! $requestedCompany) {
            if ($companyIds->count() !== 1) {
                return response()->json([
                    'message' => 'Sélectionnez explicitement une entreprise.',
                    'code' => 'COMPANY_REQUIRED',
                ], 422);
            }

            $requestedCompany = (int) $companyIds->first();
            $request->headers->set('company', (string) $requestedCompany);
        }

        $requestedCompany = (int) $requestedCompany;

        if (! $companyIds->contains($requestedCompany)) {
            return response()->json([
                'message' => 'Vous n’avez pas accès à cette entreprise.',
                'code' => 'COMPANY_FORBIDDEN',
            ], 403);
        }

        $this->context->set($requestedCompany);

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
