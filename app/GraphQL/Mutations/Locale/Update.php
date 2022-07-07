<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\GraphQL\Objects\Locale;
use App\Services\I18n\I18n;

class Update {
    public function __construct(
        protected I18n $i18n,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(Locale $locale, array $args): bool {
        $input        = new UpdateInput($args['input']);
        $translations = [];

        foreach ($input->translations ?? [] as $value) {
            $translations[$value->key] = $value->value;
        }

        return $this->i18n->setTranslations($locale->name, $translations);
    }
}
