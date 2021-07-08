<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;

use function array_unique;

class DeleteApplicationTranslations {
    public function __construct(
        protected AppDisk $disk,
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
        $translations = $storage->load();
        $deleted      = [];

        // Update
        foreach ($keys as $key) {
            if (isset($translations[$key])) {
                $deleted[] = $key;

                unset($translations[$key]);
            }
        }

        // Save
        $storage->save($translations);

        // Return
        return [
            'deleted' => array_unique($deleted),
        ];
    }

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations($this->disk, $locale);
    }
}
