<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class StorageTranslationsFailedToSave extends Exception implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct(__('graphql.mutations.storageTranslation.failed_to_save'), 0, $previous);
    }
}
