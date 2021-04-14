<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;

use function array_values;

class UpdateApplicationTranslations {
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

        // Return
        return [
            'updated' => array_values($updated),
        ];
    }

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations($this->disk, $locale);
    }
}
