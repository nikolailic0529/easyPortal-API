<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\GraphQL\Queries\Application\Settings as SettingsQuery;
use App\Services\Settings\Settings;
use Illuminate\Support\Collection;

/**
 * @see \App\GraphQL\Validators\UpdateApplicationSettingsInputValidator
 */
class UpdateApplicationSettings {
    public function __construct(
        protected Settings $settings,
        protected SettingsQuery $query,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array{setting: array<string, string>}
     */
    public function __invoke(mixed $root, array $args): array {
        $settings = (new Collection($args['input']))
            ->filter(static function (array $setting): bool {
                return isset($setting['name']) && isset($setting['value']);
            })
            ->keyBy(static function (array $setting): string {
                return $setting['name'];
            })
            ->map(static function (array $setting): string {
                return $setting['value'];
            })
            ->all();
        $updated  = $this->settings->setEditableSettings($settings);
        $updated  = $this->query->map($updated);

        return [
            'updated' => $updated,
        ];
    }
}
