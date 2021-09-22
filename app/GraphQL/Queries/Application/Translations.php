<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\TranslationDefaults;
use App\Services\I18n\TranslationLoader;
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
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string, array{key: string, value: string, default: string|null}>
     */
    public function __invoke($_, array $args): array {
        return $this->getTranslations($args['locale']);
    }

    /**
     * @return array<string, array{key: string, value: string, default: string|null}>
     */
    public function getTranslations(string $locale): array {
        $translations = $this->translations->getTranslations($locale);
        $default      = $this->defaults->getTranslations($locale);
        $output       = [];

        foreach ($translations as $key => $value) {
            $output[] = [
                'key'     => $key,
                'value'   => $value,
                'default' => $default[$key] ?? null,
            ];
        }

        return $output;
    }
}
