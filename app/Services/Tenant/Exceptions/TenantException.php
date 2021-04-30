<?php declare(strict_types = 1);

namespace App\Services\Tenant\Exceptions;

use App\Exceptions\HasErrorCode;
use Exception;

abstract class TenantException extends Exception {
    use HasErrorCode;
}
