<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions\Auth;

use App\Models\User;
use App\Services\Auth\Exceptions\AuthException;
use Throwable;

use function sprintf;
use function trans;

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
        return trans('auth.user_disabled');
    }
}
