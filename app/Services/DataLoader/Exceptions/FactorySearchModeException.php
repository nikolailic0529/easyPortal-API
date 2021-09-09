<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\ServiceException;
use Throwable;

/**
 * Special exception that indicates that the factory cannot find the object.
 */
class FactorySearchModeException extends ServiceException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('', $previous);
    }
}
