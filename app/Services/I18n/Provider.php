<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\I18n\Commands\LocaleExport;
use App\Services\I18n\Commands\LocaleImport;
use App\Utils\Providers\ServiceServiceProvider;
use Illuminate\Contracts\Container\Container;

class Provider extends ServiceServiceProvider {
    public function register(): void {
        parent::register();

        // Needed to hold Locale/Timezone
        $this->app->bind(Formatter::class, static function (Container $container): Formatter {
            $formatter = $container->make(CurrentFormatter::class);
            $formatter = $formatter
                ->forTimezone($formatter->getTimezone())
                ->forLocale($formatter->getLocale());

            return $formatter;
        });
    }

    public function boot(): void {
        $this->commands(
            LocaleExport::class,
            LocaleImport::class,
        );
    }
}
