<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Storages\ClientSettings;
use Illuminate\Support\Collection;

use function array_unique;

class DeleteClientSettings {
    public function __construct(
        protected ClientSettings $storage,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $names   = $args['input']['names'];
        $setting = (new Collection($this->storage->load()))->keyBy(static function (array $setting): string {
            return $setting['name'];
        });
        $deleted = [];

        // Update
        foreach ($names as $key) {
            if (isset($setting[$key])) {
                $deleted[] = $key;

                unset($setting[$key]);
            }
        }

        // Save
        $this->storage->save($setting->values()->all());

        // Return
        return [
            'deleted' => array_unique($deleted),
        ];
    }
}
