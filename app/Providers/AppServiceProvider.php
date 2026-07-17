<?php

namespace Crater\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Paginator::useBootstrapThree();
        $this->loadJsonTranslationsFrom(resource_path('scripts/locales'));

        /*
         * Les menus sont désormais lus directement depuis config/crater.php.
         * Cela supprime l’état global Lavary et rend le démarrage compatible
         * avec les workers persistants et le contexte multi-entreprises.
         */
        if (Storage::disk('local')->has('database_created') && Schema::hasTable('abilities')) {
            config()->set('autofacture.application_ready', true);
        }
    }
}
