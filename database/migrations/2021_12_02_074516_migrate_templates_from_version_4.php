<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateTemplatesFromVersion4 extends Migration
{
    /**
     * Run the migrations.
     *
     * This legacy migration used to assume that every Blade template had a
     * same-named PNG in public/assets. AutoFacture now supports modern templates
     * rendered with a CSS fallback preview, so a missing legacy image must not
     * prevent a fresh installation from completing.
     */
    public function up()
    {
        $this->copyLegacyPreviews('invoice');
        $this->copyLegacyPreviews('estimate');
    }

    private function copyLegacyPreviews(string $documentType): void
    {
        $templates = Storage::disk('views')->files("/app/pdf/{$documentType}");

        foreach ($templates as $template) {
            $templateName = Str::before(basename($template), '.blade.php');
            $resourceTarget = resource_path("/static/img/PDF/{$templateName}.png");
            $publicSource = public_path("/assets/img/PDF/{$templateName}.png");

            if (file_exists($resourceTarget) || ! file_exists($publicSource)) {
                continue;
            }

            $buildTarget = public_path("/build/img/PDF/{$templateName}.png");

            if (! is_dir(dirname($buildTarget))) {
                mkdir(dirname($buildTarget), 0755, true);
            }

            if (! is_dir(dirname($resourceTarget))) {
                mkdir(dirname($resourceTarget), 0755, true);
            }

            copy($publicSource, $buildTarget);
            copy($publicSource, $resourceTarget);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // The legacy migration only copied static preview files.
    }
}
