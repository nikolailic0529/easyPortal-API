<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\I18n\Translation\Translations;

class RecoverApplicationTranslations {
    public function __construct(
        protected Translations $translations,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        return [
            'result' => $this->translations->reset($args['input']['locale']),
        ];
    }
}
