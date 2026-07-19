<?php

namespace Crater\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Crater\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Crater\Http\Middleware\TrustProxies::class,
        \Crater\Http\Middleware\ConfigMiddleware::class,
        \Crater\Http\Middleware\EnsureFeatureIsEnabled::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \Crater\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Crater\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:180,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth' => \Crater\Http\Middleware\Authenticate::class,
        'bouncer' => \Crater\Http\Middleware\ScopeBouncer::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \Crater\Http\Middleware\RedirectIfAuthenticated::class,
        'customer' => \Crater\Http\Middleware\CustomerRedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'install' => \Crater\Http\Middleware\InstallationMiddleware::class,
        'redirect-if-installed' => \Crater\Http\Middleware\RedirectIfInstalled::class,
        'redirect-if-unauthenticated' => \Crater\Http\Middleware\RedirectIfUnauthorized::class,
        'customer-guest' => \Crater\Http\Middleware\CustomerGuest::class,
        'company' => \Crater\Http\Middleware\CompanyMiddleware::class,
        'pdf-auth' => \Crater\Http\Middleware\PdfMiddleware::class,
        'cron-job' => \Crater\Http\Middleware\CronJobMiddleware::class,
        'customer-portal' => \Crater\Http\Middleware\CustomerPortalMiddleware::class,
    ];

    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Crater\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Crater\Http\Middleware\CompanyMiddleware::class,
        \Crater\Http\Middleware\ScopeBouncer::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
