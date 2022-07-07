<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\I18n\Translation\Translations;

/**
 * @deprecated Please {@see \App\GraphQL\Mutations\Locale\Update}
 */
class DeleteApplicationTranslations {
    public function __construct(
        protected Translations $translations,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $keys    = $args['input']['keys'];
        $locale  = $args['input']['locale'];
        $deleted = [];
        $result  = $this->translations->delete($locale, $keys, $deleted);

        return [
            'result'  => $result,
            'deleted' => $deleted,
        ];
    }
}
