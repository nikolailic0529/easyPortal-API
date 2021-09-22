<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\GraphQL\Queries\Application\Translations;
use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;
use Illuminate\Support\Collection;

class UpdateApplicationTranslations {
    public function __construct(
        protected AppDisk $disk,
        protected Translations $query,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $inputTranslations = $args['input']['translations'];
        $locale            = $args['input']['locale'];
        $storage           = $this->getStorage($locale);
        $translations      = $storage->load();
        $updated           = [];

        // Update
        foreach ($inputTranslations as $translation) {
            $translations[$translation['key']] = $translation['value'];
            $updated[$translation['key']]      = $translation;
        }

        // Save
        $storage->save($translations);

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
            'updated' => $updated,
        ];
    }

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations($this->disk, $locale);
    }
}
