<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;

class StorageTranslations {
    public function __construct(
        protected UIDisk $disk,
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
        return $this->getStorage($args['locale'])->load();
    }

    protected function getStorage(string $locale): UITranslations {
        return new UITranslations($this->disk, $locale);
    }
}
