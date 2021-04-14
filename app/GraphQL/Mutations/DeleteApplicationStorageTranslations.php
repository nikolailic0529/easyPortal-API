<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;
use Illuminate\Support\Collection;

use function array_unique;

class DeleteApplicationStorageTranslations {
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
        $keys         = $args['input']['keys'];
        $locale       = $args['input']['locale'];
        $storage      = $this->getStorage($locale);
        $translations = (new Collection($storage->load()))->keyBy(static function (array $translation): string {
            return $translation['key'];
        });
        $deleted      = [];

        // Update
        foreach ($keys as $key) {
            if (isset($translations[$key])) {
                $deleted[] = $key;

                unset($translations[$key]);
            }
        }

        // Save
        $storage->save($translations->values()->all());

        // Return
        return [
            'deleted' => array_unique($deleted),
        ];
    }

    protected function getStorage(string $locale): UITranslations {
        return new UITranslations($this->disk, $locale);
    }
}
