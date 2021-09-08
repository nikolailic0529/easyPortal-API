<?php declare(strict_types = 1);

namespace App\Services\Queue\Exceptions;

use App\Services\Queue\ServiceException;

/**
 * @internal
 */
class JobStopped extends ServiceException {
    public function __construct() {
        parent::__construct('Job stopped.');
    }
}
