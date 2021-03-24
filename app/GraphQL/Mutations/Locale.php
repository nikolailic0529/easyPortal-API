<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\LocaleService;

class Locale {
    public function __construct(
        protected LocaleService $locale,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): bool {
        $locale = $args['locale'];
        $this->locale->set($locale);
        return true;
    }
}
