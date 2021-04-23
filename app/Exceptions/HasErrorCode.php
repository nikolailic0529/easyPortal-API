<?php declare(strict_types = 1);

namespace App\Exceptions;

trait HasErrorCode {
    public function getErrorCode(): string|int {
        /** @var \Throwable $this */
        return ErrorCodes::getCode($this);
    }
}
