<?php declare(strict_types = 1);

namespace App\Services\Queue\Exceptions;

use App\Services\Queue\ServiceException;
use App\Utils\Iterators\Contracts\IteratorFatalError;

/**
 * @internal
 */
class JobStopped extends ServiceException implements IteratorFatalError {
    public function __construct() {
        parent::__construct('Job stopped.');
    }
}
