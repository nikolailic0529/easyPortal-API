<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class StorageTranslationsFileCorrupted extends Exception implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct(__('graphql.queries.application.storage.translations.file_corrupted'), 0, $previous);
    }
}
