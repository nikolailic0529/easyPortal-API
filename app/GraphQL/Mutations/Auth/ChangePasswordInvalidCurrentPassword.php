<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class ChangePasswordInvalidCurrentPassword extends Exception implements TranslatedException {
    use HasErrorCode;

    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid old password.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.changePassword.invalid_current_password');
    }
}
