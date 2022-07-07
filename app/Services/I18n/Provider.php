<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\I18n\Commands\LocaleExport;
use App\Services\I18n\Commands\LocaleImport;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;

class Provider extends ServiceProvider {
    use ProviderWithCommands;

    public function boot(): void {
        $this->bootCommands(
            LocaleExport::class,
            LocaleImport::class,
        );
    }
}
