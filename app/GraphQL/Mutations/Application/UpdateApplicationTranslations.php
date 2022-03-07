<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\GraphQL\Queries\Application\Translations as TranslationsQuery;
use App\Services\I18n\Translation\Translations;
use Illuminate\Support\Collection;

use function array_fill_keys;
use function array_intersect_key;

class UpdateApplicationTranslations {
    public function __construct(
        protected Translations $translations,
        protected TranslationsQuery $query,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        // Prepare
        $translations = $args['input']['translations'];
        $locale       = $args['input']['locale'];
        $strings      = [];
        $original     = [];

        foreach ($translations as $translation) {
            $strings[$translation['key']]  = $translation['value'];
            $original[$translation['key']] = $translation;
        }

        // Save
        $updated = [];
        $result  = $this->translations->update($locale, $strings, $updated);

        if ($result) {
            $updated = array_intersect_key($original, array_fill_keys($updated, null));
        }

        // Add default
        $updated = (new Collection($this->query->getTranslations($locale)))
            ->map(static function (array $translation) use ($updated): ?array {
                return isset($updated[$translation['key']])
                    ? $updated[$translation['key']] + $translation
                    : null;
            })
            ->filter()
            ->values()
            ->all();

        // Return
        return [
            'result'  => $result,
            'updated' => $updated,
        ];
    }
}
