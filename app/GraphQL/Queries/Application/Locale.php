<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\CurrentLocale;

class Locale {
    public function __construct(
        protected CurrentLocale $locale,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $root, array $args): string {
        return $this->locale->get();
    }
}
