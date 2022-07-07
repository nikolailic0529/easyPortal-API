<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\GraphQL\Objects\Locale;
use App\Services\I18n\I18n;

class Reset {
    public function __construct(
        protected I18n $i18n,
    ) {
        // empty
    }

    public function __invoke(Locale $locale): bool {
        return $this->i18n->resetTranslations($locale->name);
    }
}
