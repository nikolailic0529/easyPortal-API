<?php declare(strict_types = 1);

namespace App\Services\Organization\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Organization\ServiceException;
use Throwable;

use function trans;

class UnknownOrganization extends ServiceException implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Organization is unknown.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('organization.errors.unknown_organization');
    }
}
