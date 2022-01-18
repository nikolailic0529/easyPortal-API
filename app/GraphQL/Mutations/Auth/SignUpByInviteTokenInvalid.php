<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;

class SignUpByInviteTokenInvalid extends GraphQLException {
    public function __construct(protected mixed $token, Throwable $previous = null) {
        parent::__construct('Invalid invite token.', $previous);

        $this->setContext([
            'token' => $this->token,
        ]);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.signUpByInvite.token_invalid');
    }
}
