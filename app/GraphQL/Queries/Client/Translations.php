<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

use App\Services\I18n\I18n;

class Translations {
    public function __construct(
        protected I18n $i18n,
    ) {
        // empty
    }

    /**
     * @param array{locale: string} $args
     *
     * @return array<array{key: string, value: string}>
     */
    public function __invoke(mixed $root, array $args): array {
        $strings      = $this->i18n->getClientTranslations($args['locale']);
        $translations = [];

        foreach ($strings as $key => $string) {
            $translations[] = [
                'key'   => $key,
                'value' => $string,
            ];
        }

        return $translations;
    }
}
