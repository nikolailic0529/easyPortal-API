<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\GraphQL\Objects\Locale as LocaleObject;
use App\Services\I18n\I18n;

class Locale {
    public function __construct(
        protected I18n $i18n,
    ) {
        // empty
    }

    /**
     * @param array{name: string} $args
     */
    public function __invoke(mixed $root, array $args): LocaleObject {
        return new LocaleObject(['name' => $args['name']]);
    }

    /**
     * @return array<array{key: string, value: string, default: ?string}>
     */
    public function translations(LocaleObject $root): array {
        $locale       = $root->name;
        $translations = $this->i18n->getTranslations($locale);
        $default      = $this->i18n->getDefaultTranslations($locale);
        $output       = [];

        foreach ($translations as $key => $value) {
            $output[$key] = [
                'key'     => $key,
                'value'   => $value,
                'default' => $default[$key] ?? null,
            ];
        }

        return $output;
    }
}
