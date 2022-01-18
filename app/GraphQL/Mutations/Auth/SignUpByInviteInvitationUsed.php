<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use App\Models\Invitation;
use Throwable;

use function __;
use function sprintf;

class SignUpByInviteInvitationUsed extends GraphQLException {
    public function __construct(Invitation $invitation, Throwable $previous = null) {
        parent::__construct(sprintf(
            'User already used invitation `%s` .',
            $invitation->getKey(),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.signUpByInvite.invitation_used');
    }
}
