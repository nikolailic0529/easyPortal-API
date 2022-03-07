<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\I18n\Translation\Translations;

class DeleteApplicationTranslations {
    public function __construct(
        protected Translations $translations,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
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
