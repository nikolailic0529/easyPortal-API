<?php declare(strict_types = 1);

namespace App\Services\Organization\Exceptions;

use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class UnknownOrganization extends OrganizationException implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Organization is unknown.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('organization.errors.unknown_organization');
    }
}
