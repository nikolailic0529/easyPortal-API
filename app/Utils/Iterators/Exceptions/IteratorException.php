<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Exceptions;

use App\Exceptions\ApplicationException;
use App\Exceptions\Contracts\GenericException;

abstract class IteratorException extends ApplicationException implements GenericException {
    // empty
}
