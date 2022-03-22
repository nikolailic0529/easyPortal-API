<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Storages\ClientSettings;

class RecoverClientSettings {
    public function __construct(
        protected ClientSettings $storage,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array{result: bool}
     */
    public function __invoke(mixed $root, array $args): array {
        return [
            'result' => $this->storage->delete(true),
        ];
    }
}
