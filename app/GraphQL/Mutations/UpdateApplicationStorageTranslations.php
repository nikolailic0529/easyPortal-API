<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;
use Illuminate\Support\Collection;

use function array_values;

class UpdateApplicationStorageTranslations {
    public function __construct(
        protected UIDisk $disk,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $inputTranslations = $args['input']['translations'];
        $locale            = $args['input']['locale'];
        $storage           = $this->getStorage($locale);
        $translations      = $storage->load();
        $updated           = [];

        // Update
        $translations = (new Collection($translations))->keyBy(static function ($translation): string {
            return $translation['key'];
        });

        foreach ($inputTranslations as $translation) {
            $translations->put($translation['key'], $translation);
            $updated[$translation['key']] = $translation;
        }

        // Save
        $storage->save($translations->values()->all());

        // Return
        return [
            'updated' => array_values($updated),
        ];
    }

    protected function getStorage(string $locale): UITranslations {
        return new UITranslations($this->disk, $locale);
    }
}
