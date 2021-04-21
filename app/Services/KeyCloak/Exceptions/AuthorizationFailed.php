<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use Throwable;

use function __;

class AuthorizationFailed extends KeyCloakException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Authorization failed.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('auth.failed');
    }
}
