<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions\Auth;

use App\Models\User;
use App\Services\Auth\Exceptions\AuthException;
use Throwable;

use function __;
use function sprintf;

class AnotherUserExists extends AuthException {
    public function __construct(
        protected User $user,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Another user `%s` already exists.',
            $this->user->getKey(),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.auth.another_user_exists');
    }
}