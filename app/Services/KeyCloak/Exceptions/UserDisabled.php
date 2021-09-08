<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Models\User;
use Throwable;

use function __;
use function sprintf;

class UserDisabled extends AuthException {
    public function __construct(
        protected User $user,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'User `%s` is disabled.',
            $this->user->getKey(),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('auth.user_disabled');
    }
}
