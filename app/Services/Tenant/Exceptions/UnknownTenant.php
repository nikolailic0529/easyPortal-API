<?php declare(strict_types = 1);

namespace App\Services\Tenant\Exceptions;

use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class UnknownTenant extends TenantException implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Tenant is unknown.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('tenant.errors.unknown_tenant');
    }
}
