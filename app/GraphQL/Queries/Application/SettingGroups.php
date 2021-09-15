<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Settings\Settings as SettingsService;

class SettingGroups {
    public function __construct(
        protected SettingsService $settings,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        $settings = $this->settings->getEditableSettings();
        $groups   = [];

        foreach ($settings as $setting) {
            // Setting?
            if ($setting->isService() || $setting->isJob()) {
                continue;
            }

            // Has Group?
            $group = $setting->getGroup();

            if (!$group) {
                continue;
            }

            // Add
            if (isset($groups[$group])) {
                $groups[$group]['settings'][] = $setting->getName();
            } else {
                $groups[$group] = [
                    'name'     => $group,
                    'settings' => [
                        $setting->getName(),
                    ],
                ];
            }
        }

        return $groups;
    }
}
