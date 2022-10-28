<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

use Throwable;

/**
 * Exceptions that implement this interface will be treated as a fatal error
 * and iteration will be stopped.
 */
interface IteratorFatalError extends Throwable {
    // empty
}
