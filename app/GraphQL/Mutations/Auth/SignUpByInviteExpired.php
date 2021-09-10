<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;

class SignUpByInviteExpired extends GraphQLException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invitation is expired.', $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.signUpByInvite.expired');
    }
}
