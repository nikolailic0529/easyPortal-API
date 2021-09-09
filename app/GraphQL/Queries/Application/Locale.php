<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\Locale as LocaleService;

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
    public function __invoke($_, array $args): string {
        return $this->locale->get();
    }
}
