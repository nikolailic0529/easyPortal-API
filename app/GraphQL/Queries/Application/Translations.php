<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Storages\AppTranslations;

class Translations {
    public function __construct(
        protected AppDisk $disk,
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
        $translations = $this->getStorage($args['locale'])->load();
        $output       = [];

        foreach ($translations as $key => $value) {
            $output[] = [
                'key'   => $key,
                'value' => $value,
            ];
        }

        return $output;
    }

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations($this->disk, $locale);
    }
}
