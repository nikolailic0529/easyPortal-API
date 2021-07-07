<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\LocaleService;

class SetApplicationLocale {
    public function __construct(
        protected LocaleService $locale,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string,bool>
     */
    public function __invoke($_, array $args): array {
        $locale = $args['input']['locale'];
        $this->locale->set($locale);
        return ['result' => true ];
    }
}
