<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Models\User;
use Throwable;

use function __;
use function sprintf;

class AnotherUserExists extends KeyCloakException {
    public function __construct(
        protected User $user,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Another user `%s` already exists.',
            $this->user->getKey(),
        ), 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('auth.failed');
    }
}
