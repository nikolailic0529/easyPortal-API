<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Models\Organization;
use Throwable;

use function __;
use function sprintf;

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
        return __('auth.organization_disabled');
    }
}
