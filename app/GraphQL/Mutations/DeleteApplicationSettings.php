<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Storage;

class DeleteApplicationSettings {
    public function __construct(
        protected Storage $storage,
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
