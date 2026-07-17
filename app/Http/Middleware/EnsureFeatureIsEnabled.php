<?php

namespace Crater\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFeatureIsEnabled
{
    /**
     * Interdit l'accès direct aux fonctions désactivées dans AutoFacture.
     *
     * Les menus sont déjà filtrés dans GeneratesMenuTrait. Ce middleware
     * complète la protection en bloquant également les URL saisies à la main
     * et les appels directs à l'API historique de Crater.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
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

            foreach ((array) $patterns as $pattern) {
                if ($request->is($pattern)) {
                    return $feature;
                }
            }
        }

        foreach (config('autofacture.settings_route_patterns', []) as $feature => $patterns) {
            if ((bool) config("autofacture.settings_features.{$feature}", false)) {
                continue;
            }

            foreach ((array) $patterns as $pattern) {
                if ($request->is($pattern)) {
                    return $feature;
                }
            }
        }

        return null;
    }
}
