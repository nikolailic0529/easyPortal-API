<?php declare(strict_types = 1);

namespace App\Services\Keycloak;

use Illuminate\Contracts\Validation\Factory;

use function app;
use function array_search;

class Map {
    /**
     * @var array<string,string>
     */
    protected static array $locales = [
        'de' => 'de_DE',
        'en' => 'en_GB',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
    ];

    public static function getKeycloakLocale(?string $appLocale): ?string {
        return array_search($appLocale, self::$locales, true) ?: $appLocale;
    }

    public static function getAppLocale(?string $keycloakLocale): ?string {
        return self::$locales[$keycloakLocale] ?? null;
    }

    public static function getAppTimezone(?string $keycloakTimezone): ?string {
        $factory   = app()->make(Factory::class);
        $validator = $factory->make(['value' => $keycloakTimezone], ['value' => 'nullable|timezone']);
        $timezone  = $validator->fails() ? null : $keycloakTimezone;

        return $timezone;
    }
}
