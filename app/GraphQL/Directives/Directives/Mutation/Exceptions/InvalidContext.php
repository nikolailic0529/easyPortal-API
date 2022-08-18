<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Exceptions;

use App\GraphQL\Directives\Directives\Mutation\MutationException;
use Throwable;

use function trans;

class InvalidContext extends MutationException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid context.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('graphql.directives.@mutation.invalid_context');
    }
}
