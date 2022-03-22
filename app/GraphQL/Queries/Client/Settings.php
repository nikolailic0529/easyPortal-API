<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

use App\Services\Settings\Settings as SettingsService;
use App\Services\Settings\Storages\ClientSettings;
use Illuminate\Support\Collection;

class Settings {
    public function __construct(
        protected ClientSettings $storage,
        protected SettingsService $settings,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $settings = (new Collection($this->storage->load()))->keyBy(static function (array $setting): string {
            return $setting['name'];
        });

        foreach ($this->settings->getPublicSettings() as $name => $value) {
            $settings[$name] = [
                'name'  => $name,
                'value' => $value,
            ];
        }

        return $settings->all();
    }
}
