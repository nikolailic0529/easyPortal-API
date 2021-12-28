<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\GraphQL\GraphQLException;
use App\Models\Role;
use Throwable;

use function __;
use function sprintf;

class DeleteImpossibleAssignedToUsers extends GraphQLException {
    public function __construct(Role $role, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Impossible to delete the role `%s` because it is assigned to the user(s).',
            $role->getKey(),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.org.role.delete.impossible_assigned_to_users');
    }
}
