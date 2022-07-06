<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

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
    public function __invoke(mixed $root, array $args): string {
        return $args['name'];
    }

    public function name(string $root): string {
        return $root;
    }

    /**
     * @return array<array{key: string, value: string, default: ?string}>
     */
    public function translations(string $root): array {
        $translations = $this->i18n->getTranslations($root);
        $default      = $this->i18n->getDefaultTranslations($root);
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
