<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Storages\ClientSettings;

class RecoverClientSettings {
    public function __construct(
        protected ClientSettings $storage,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array{result: bool}
     */
    public function __invoke($_, array $args): array {
        return [
            'result' => $this->storage->delete(true),
        ];
    }
}
