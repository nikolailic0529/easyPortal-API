<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\GraphQLException;
use App\Models\Invitation;
use Throwable;

use function sprintf;
use function trans;

class SignUpByInviteInvitationOrganizationNotFound extends GraphQLException {
    public function __construct(Invitation $invitation, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Organization associated with Invitation `%s` not found.',
            $invitation->getKey(),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return trans('graphql.mutations.signUpByInvite.invitation_organization_not_found');
    }
}
