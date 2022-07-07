<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\GraphQL\Objects\Locale;
use App\Services\I18n\I18n;
use App\Services\I18n\Storages\Spreadsheet;

class Import {
    public function __construct(
        protected I18n $i18n,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(Locale $locale, array $args): bool {
        $input        = new ImportInput($args['input']);
        $storage      = new Spreadsheet($input->translations);
        $translations = $storage->load();
        $result       = $this->i18n->setTranslations($locale->name, $translations);

        return $result;
    }
}
