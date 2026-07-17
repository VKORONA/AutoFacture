<?php

namespace Crater\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFeatureIsEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $feature = $this->disabledFeatureFor($request);

        if ($feature === null) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Cette fonction n\'est pas disponible dans cette version d\'AutoFacture.',
                'code' => 'FEATURE_DISABLED',
                'feature' => $feature,
            ], 404);
        }

        if ($request->is('admin/*')) {
            return redirect('/admin/dashboard');
        }

        abort(404);
    }

    private function disabledFeatureFor(Request $request): ?string
    {
        foreach (config('autofacture.route_patterns', []) as $feature => $patterns) {
            if ((bool) config("autofacture.features.{$feature}", false)) {
                continue;
            }

            if ($this->matches($request, $patterns)) {
                return $feature;
            }
        }

        foreach (config('autofacture.settings_route_patterns', []) as $feature => $patterns) {
            if ((bool) config("autofacture.settings_features.{$feature}", false)) {
                continue;
            }

            if ($this->matches($request, $patterns)) {
                return $feature;
            }
        }

        if (! $request->isMethodSafe()) {
            foreach (config('autofacture.settings_write_route_patterns', []) as $feature => $patterns) {
                if ((bool) config("autofacture.settings_features.{$feature}", false)) {
                    continue;
                }

                if ($this->matches($request, $patterns)) {
                    return $feature;
                }
            }
        }

        return null;
    }

    private function matches(Request $request, $patterns): bool
    {
        foreach ((array) $patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
