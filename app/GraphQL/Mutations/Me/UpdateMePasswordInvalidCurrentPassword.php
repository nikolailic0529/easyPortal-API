<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;

class UpdateMePasswordInvalidCurrentPassword extends GraphQLException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid old password.', $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.updateMePassword.invalid_current_password');
    }
}
