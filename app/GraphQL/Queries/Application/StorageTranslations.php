<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\GraphQL\Mutations\UpdateApplicationStorageTranslations;
use App\Services\Filesystem;

use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

class StorageTranslations {
    public function __construct(
        protected Filesystem $filesystem,
        protected UpdateApplicationStorageTranslations $mutation,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        $disc         = $this->filesystem->disk($this->mutation->getDisc());
        $translations = [];
        $file         = $this->mutation->getFile($args['locale']);

        if ($disc->exists($file)) {
            $translations = json_decode($disc->get($file), true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new StorageTranslationsQueryFileCorrupted();
        }

        return $translations;
    }
}
