<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\I18n\Storages\ClientTranslations;
use Illuminate\Support\Collection;

use function array_values;

class UpdateClientTranslations {
    public function __construct(
        protected ClientDisk $disk,
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

    protected function getStorage(string $locale): ClientTranslations {
        return new ClientTranslations($this->disk, $locale);
    }
}
