<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Illuminate\Contracts\Foundation\Application;

class Translations {
    public function __construct(
        protected Application $app,
        protected TranslationLoader $translations,
        protected TranslationDefaults $defaults,
    ) {
        // empty
    }

    /**
     * @param array{locale: string} $args
     *
     * @return array<string, array{key: string, value: string, default: string|null}>
     */
    public function __invoke(mixed $root, array $args): array {
        $locale       = $args['locale'];
        $default      = $this->app->getLocale();
        $translations = $this->getTranslations($locale);

        if ($locale !== $default) {
            $translations += $this->getTranslations($default);
        }

        return $translations;
    }

    /**
     * @return array<string, array{key: string, value: string, default: string|null}>
     */
    public function getTranslations(string $locale): array {
        $translations = $this->translations->getTranslations($locale);
        $default      = $this->defaults->getTranslations($locale);
        $output       = [];

        foreach ($translations as $key => $value) {
            $output[$key] = [
                'key'     => $key,
                'value'   => $value,
                'default' => $default[$key] ?? null,
            ];
        }

        foreach ($default as $key => $value) {
            if (!isset($output[$key])) {
                $output[$key] = [
                    'key'     => $key,
                    'value'   => $value,
                    'default' => $value,
                ];
            }
        }

        return $output;
    }
}
