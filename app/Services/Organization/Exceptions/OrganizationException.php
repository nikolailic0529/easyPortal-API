<?php declare(strict_types = 1);

namespace App\Services\Organization\Exceptions;

use App\Exceptions\HasErrorCode;
use Exception;

abstract class OrganizationException extends Exception {
    use HasErrorCode;
}
