<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Settings\Setting;
use App\Services\Settings\Settings as SettingsService;

use function array_map;

class Settings {
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
        return $this->map($this->settings->getEditableSettings());
    }

    /**
     * @param array<\App\Services\Settings\Setting> $settings
     *
     * @return array<mixed>
     */
    public function map(array $settings): array {
        return array_map(function (Setting $setting): array {
            return $this->toArray($setting);
        }, $settings);
    }

    /**
     * @return array<mixed>
     */
    protected function toArray(Setting $setting): array {
        return [
            'name'        => $setting->getName(),
            'type'        => $setting->getTypeName(),
            'array'       => $setting->isArray(),
            'value'       => $this->settings->serializeValue($setting, $setting->getValue()),
            'secret'      => $setting->isSecret(),
            'default'     => $this->settings->serializeValue($setting, $setting->getDefaultValue()),
            'readonly'    => $setting->isReadonly(),
            'job'         => $setting->isJob(),
            'service'     => $setting->isService(),
            'description' => $setting->getDescription(),
        ];
    }
}
