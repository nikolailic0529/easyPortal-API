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
        $deleted           = [];

        // Update
        $translations = (new Collection($translations))->keyBy(static function ($translation): string {
            return $translation['key'];
        });

        foreach ($inputTranslations as $translation) {
            if ($translation['delete']) {
                if (!$translations->has($translation['key'])) {
                    // So it doesn't return false deleted
                    continue;
                }
                $deleted[$translation['key']] = $translation;
                $translations->forget($translation['key']);
            } else {
                $data = [
                    'key'   => $translation['key'],
                    'value' => $translation['value'],
                ];
                $translations->put($translation['key'], $data);
                $updated[$translation['key']] = $data;
            }
        }

        // Save
        $storage->save($translations->values()->all());

        // Return
        return [
            'updated' => array_values($updated),
            'deleted' => array_values($deleted),
        ];
    }

    protected function getStorage(string $locale): UITranslations {
        return new UITranslations($this->disk, $locale);
    }
}
