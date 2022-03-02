<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Organization\User;

use App\GraphQL\GraphQLException;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use Throwable;

use function __;
use function sprintf;

class InviteImpossibleKeyCloakUserDisabled extends GraphQLException {
    public function __construct(KeycloakUser $user, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Impossible to invite the user `%s` because it is disabled.',
            $user->id,
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.organization.user.invite.impossible_keycloak_user_disabled');
    }
}
