<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;

class SignUpByInviteInvalidToken extends GraphQLException {
    public function __construct(string $id, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Invalid invite token with id `%s`.',
            $id,
        ), $previous);
        parent::__construct('Invalid invite token.', $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.signUpByInvite.invalid_token');
    }
}
