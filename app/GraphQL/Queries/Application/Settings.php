<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Settings\Setting;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Support\Collection;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function is_null;

class Settings {
    protected const SECRET = '********';

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
            'value'       => $this->toString($setting, $setting->getValue()),
            'secret'      => $setting->isSecret(),
            'default'     => $this->toString($setting, $setting->getDefaultValue()),
            'readonly'    => $setting->isReadonly(),
            'description' => $setting->getDescription(),
        ];
    }

    protected function toString(Setting $setting, mixed $value): string {
        $type   = $setting->getType();
        $value  = $this->hide($setting, $value);
        $string = null;

        if ($setting->isArray() && !is_null($value)) {
            $string = (new Collection((array) $value))
                ->map(static function (mixed $value) use ($type): string {
                    return $type->toString($value);
                })
                ->implode(SettingsService::DELIMITER);
        } else {
            $string = $type->toString($value);
        }

        return $string;
    }

    protected function hide(Setting $setting, mixed $value): mixed {
        if ($setting->isSecret()) {
            if ($setting->isArray() && !is_null($value)) {
                $value = array_fill_keys(array_keys((array) $value), static::SECRET);
            } else {
                $value = $value ? static::SECRET : null;
            }
        }

        return $value;
    }
}
