<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;

class RecoverApplicationTranslations {
    public function __construct(
        protected AppDisk $disk,
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

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations($this->disk, $locale);
    }
}
