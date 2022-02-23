<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\ExternalException;
use App\Services\DataLoader\ServiceException;

abstract class WarrantyCheckFailed extends ServiceException implements ExternalException {
    // empty
}
