<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\Storages\ClientTranslations;
use Illuminate\Support\Collection;

use function array_unique;

class DeleteClientTranslations {
    public function __construct(
        protected ClientDisk $disk,
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

    protected function getStorage(string $locale): ClientTranslations {
        return new ClientTranslations($this->disk, $locale);
    }
}