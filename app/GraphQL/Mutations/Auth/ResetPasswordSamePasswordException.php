<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use Throwable;

use function trans;

class ResetPasswordSamePasswordException extends GraphQLException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Cannot use old password.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('graphql.mutations.resetPassword.same_password');
    }
}
