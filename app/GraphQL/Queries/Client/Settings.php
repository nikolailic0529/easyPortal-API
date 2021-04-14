<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

use App\Services\Filesystem\Storages\ClientSettings;

class Settings {
    public function __construct(
        protected ClientSettings $storage,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        return $this->storage->load();
    }
}
