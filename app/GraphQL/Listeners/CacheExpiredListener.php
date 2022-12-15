<?php declare(strict_types = 1);

namespace App\GraphQL\Listeners;

use App\GraphQL\Cache;
use App\Services\DataLoader\Events\DataImported;
use App\Services\I18n\Events\TranslationsUpdated;
use App\Services\Maintenance\Events\VersionUpdated;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Settings\Events\SettingsUpdated;
use App\Utils\Providers\EventsProvider;

class CacheExpiredListener implements EventsProvider {
    public function __construct(
        protected Cache $cache,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            DataImported::class,
            VersionUpdated::class,
            SettingsUpdated::class,
            ModelsRecalculated::class,
            TranslationsUpdated::class,
        ];
    }

    public function __invoke(): void {
        $this->cache->markExpired();
    }
}
