<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Settings;
use App\Services\Settings\Storages\ClientSettings;
use Illuminate\Support\Collection;

use function array_values;

class UpdateClientSettings {
    public function __construct(
        protected ClientSettings $storage,
        protected Settings $settings,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $inputSettings = $args['input'];
        $protected     = $this->settings->getPublicSettings();
        $settings      = $this->storage->load();
        $updated       = [];

        // Update
        $settings = (new Collection($settings))->keyBy(static function (array $setting): string {
            return $setting['name'];
        });

        foreach ($inputSettings as $setting) {
            if (!isset($protected[$setting['name']])) {
                $settings->put($setting['name'], $setting);
                $updated[$setting['name']] = $setting;
            }
        }

        // Remove protected
        foreach ($protected as $name => $value) {
            unset($settings[$name]);
        }

        // Save
        $this->storage->save($settings->values()->all());

        // Return
        return [
            'updated' => array_values($updated),
        ];
    }
}
