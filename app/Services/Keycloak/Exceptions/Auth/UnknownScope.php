<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions\Auth;

use App\Models\Organization;
use App\Services\Auth\Exceptions\AuthException;
use Throwable;

use function sprintf;
use function trans;

class UnknownScope extends AuthException {
    public function __construct(
        protected Organization $organization,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Organization `%s` does not has a scope.',
            $this->organization->getKey(),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return trans('auth.organization_disabled');
    }
}
