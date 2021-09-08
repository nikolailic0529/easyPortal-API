<?php declare(strict_types = 1);

namespace App\Exceptions;

use Throwable;

class ErrorReport {
    public function __construct(
        protected Throwable $error,
    ) {
        // empty
    }

    public function getError(): Throwable {
        return $this->error;
    }
}
