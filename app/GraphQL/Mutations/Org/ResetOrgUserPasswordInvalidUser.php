<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;

class ResetOrgUserPasswordInvalidUser extends GraphQLException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid keycloak user.', $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.resetOrgUserPassword.invalid_user');
    }
}
