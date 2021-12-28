<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Exceptions;

use App\GraphQL\Directives\Directives\Mutation\MutationException;
use Throwable;

use function __;

class ObjectNotFound extends MutationException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Object not found.', $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.directives.@mutation.object_not_found');
    }
}
