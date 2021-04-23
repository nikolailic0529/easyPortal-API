<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class DispatchApplicationServiceNotFoundException extends Exception implements TranslatedException {
    use HasErrorCode;

    public function __construct(Throwable $previous = null) {
        parent::__construct('Service not found.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.dispatchApplicationService.not_found');
    }
}
