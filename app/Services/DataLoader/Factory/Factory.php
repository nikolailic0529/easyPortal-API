<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Services\DataLoader\Container\Isolated;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * Factories implement logic on how to create an application's model from an
 * external entity.
 *
 * Important notes:
 * - factories must not cache anything
 *
 * @internal
 */
abstract class Factory implements Isolated {
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
    ) {
        // empty
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }
}
