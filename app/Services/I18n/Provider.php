<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\I18n\Commands\LocaleExport;
use App\Services\I18n\Commands\LocaleImport;
use App\Utils\Providers\ServiceServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;

class Provider extends ServiceServiceProvider {
    use ProviderWithCommands;

    public function boot(): void {
        $this->bootCommands(
            LocaleExport::class,
            LocaleImport::class,
        );
    }
}
