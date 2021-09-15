<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;
use function sprintf;

class SignUpByInviteNotFound extends GraphQLException {
    public function __construct(string $id, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Invitation `%s` not found.',
            $id,
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.signUpByInvite.not_found');
    }
}
