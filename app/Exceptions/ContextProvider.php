<?php declare(strict_types = 1);

namespace App\Exceptions;

use Throwable;

interface ContextProvider {
    /**
     * @return array<string, mixed>
     */
    public function getExceptionContext(Throwable $exception): array;
}
