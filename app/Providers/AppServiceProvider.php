<?php

namespace Crater\Providers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists('Barryvdh\\DomPDF\\Facade', false)) {
            class_alias(Pdf::class, 'Barryvdh\\DomPDF\\Facade');
        }
    }

    public function boot(): void
    {
        Paginator::useBootstrapThree();
        $this->loadJsonTranslationsFrom(resource_path('scripts/locales'));

        if (Storage::disk('local')->has('database_created') && Schema::hasTable('abilities')) {
            config()->set('autofacture.application_ready', true);
        }
    }
}
