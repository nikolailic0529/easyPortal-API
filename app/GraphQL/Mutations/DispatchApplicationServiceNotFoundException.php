<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class DispatchApplicationServiceNotFoundException extends Exception implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct(__('graphql.mutations.dispatchApplicationService.not_found'), 0, $previous);
    }
}
