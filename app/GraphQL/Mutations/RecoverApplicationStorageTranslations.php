<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\Storages\UITranslations;

class RecoverApplicationStorageTranslations {
    public function __construct(
        protected UIDisk $disk,
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
            'result' => $this->getStorage($args['input']['locale'])->delete(true),
        ];
    }

    protected function getStorage(string $locale): UITranslations {
        return new UITranslations($this->disk, $locale);
    }
}
