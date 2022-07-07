<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\I18n\CurrentLocale;

class SetApplicationLocale {
    public function __construct(
        protected CurrentLocale $locale,
    ) {
        // empty
    }
    /**
     * @param  array<string, mixed>  $args
     *
     * @return  array<string,bool>
     */
    public function __invoke(mixed $root, array $args): array {
        $locale = $args['input']['locale'];
        $this->locale->set($locale);
        return ['result' => true ];
    }
}
