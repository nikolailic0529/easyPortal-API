<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Settings\Setting;
use App\Services\Settings\Settings as SettingsService;
use Illuminate\Support\Collection;

use function array_map;
use function is_null;

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
        return array_map(function (Setting $setting): array {
            return $this->toArray($setting);
        }, $this->settings->getEditableSettings());
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
            'description' => $setting->getDescription(),
        ];
    }

    protected function toString(Setting $setting, mixed $value): string {
        $type   = $setting->getType();
        $string = null;

        if ($setting->isArray() && !is_null($value)) {
            $string = (new Collection((array) $value))
                ->map(static function (mixed $value) use ($type): string {
                    return $type->toString($value);
                })
                ->implode(',');
        } else {
            $string = $type->toString($value);
        }

        return $string;
    }
}
