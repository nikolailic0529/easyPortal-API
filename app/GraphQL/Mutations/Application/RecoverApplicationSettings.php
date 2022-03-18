<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Settings\Storage;

class RecoverApplicationSettings {
    public function __construct(
        protected Storage $storage,
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
            'result' => $this->storage->delete(),
        ];
    }
}
