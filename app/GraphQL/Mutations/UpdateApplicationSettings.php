<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Queries\Application\Settings as SettingsQuery;
use App\Services\Settings\Settings;

class UpdateApplicationSettings {
    public function __construct(
        protected Settings $settings,
        protected SettingsQuery $query,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array{setting: array<mixed>}
     */
    public function __invoke($_, array $args): array {
        return [
            'settings' => $this->query->map(
                $this->settings->setEditableSettings($args['input']),
            ),
        ];
    }
}
