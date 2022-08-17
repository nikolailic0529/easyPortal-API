<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions\Auth;

use App\Services\Auth\Exceptions\AuthException;
use Throwable;

use function trans;

class AuthorizationFailed extends AuthException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Authorization failed.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('auth.failed');
    }
}
