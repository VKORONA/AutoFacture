<?php

namespace Crater\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin/dashboard';
    public const CUSTOMER_HOME = '/customer/dashboard';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function (): void {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/autofacture.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(180)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        RateLimiter::for('company-registry', function (Request $request): Limit {
            $actor = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute(30)->by(
                'company-registry:'.$actor.':'.$request->header('company')
            );
        });
    }
}
