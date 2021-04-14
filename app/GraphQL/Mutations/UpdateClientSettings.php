<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Storages\ClientSettings;
use Illuminate\Support\Collection;

use function array_values;

class UpdateClientSettings {
    public function __construct(
        protected ClientSettings $storage,
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
        $inputSettings = $args['input'];
        $settings      = $this->storage->load();
        $updated       = [];

        // Update
        $settings = (new Collection($settings))->keyBy(static function (array $translation): string {
            return $translation['name'];
        });

        foreach ($inputSettings as $translation) {
            $settings->put($translation['name'], $translation);
            $updated[$translation['name']] = $translation;
        }

        // Save
        $this->storage->save($settings->values()->all());

        // Return
        return [
            'updated' => array_values($updated),
        ];
    }
}
