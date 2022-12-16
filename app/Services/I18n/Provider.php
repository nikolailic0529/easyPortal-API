<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\I18n\Commands\LocaleExport;
use App\Services\I18n\Commands\LocaleImport;
use App\Utils\Providers\ServiceServiceProvider;

class Provider extends ServiceServiceProvider {
    public function boot(): void {
        $this->commands(
            LocaleExport::class,
            LocaleImport::class,
        );
    }
}
