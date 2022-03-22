<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\GraphQL\Objects\StringValue;
use App\Services\Settings\Setting;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Support\Collection;

use function array_map;
use function is_string;

class Settings {
    public function __construct(
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
        return $this->map($this->settings->getEditableSettings());
    }

    /**
     * @param array<Setting> $settings
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
            'value'       => $this->settings->getPublicValue($setting),
            'values'      => $this->getValues($setting),
            'secret'      => $setting->isSecret(),
            'default'     => $this->settings->getPublicDefaultValue($setting),
            'readonly'    => $this->settings->isReadonly($setting),
            'job'         => $setting->isJob(),
            'service'     => $setting->isService(),
            'description' => $setting->getDescription(),
        ];
    }

    protected function getValues(Setting $setting): Collection|array|null {
        $values = $setting->getType()->getValues();

        foreach ((array) $values as $key => $value) {
            if (is_string($value)) {
                $values[$key] = new StringValue(['value' => $value]);
            }
        }

        return $values;
    }
}
