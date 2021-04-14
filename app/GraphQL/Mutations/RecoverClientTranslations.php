<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\Storages\ClientTranslations;

class RecoverClientTranslations {
    public function __construct(
        protected ClientDisk $disk,
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

    protected function getStorage(string $locale): ClientTranslations {
        return new ClientTranslations($this->disk, $locale);
    }
}
