<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions\Auth;

use App\Services\Auth\Exceptions\AuthException;
use Throwable;

use function __;

class AuthorizationFailed extends AuthException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Authorization failed.', $previous);
    }

    public function getErrorMessage(): string {
        return __('auth.failed');
    }
}
