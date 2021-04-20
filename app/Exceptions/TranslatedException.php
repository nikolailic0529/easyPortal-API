<?php declare(strict_types = 1);

namespace App\Exceptions;

/**
 * Marks that exception has translated error message.
 */
interface TranslatedException {
    public function getErrorMessage(): string;

    public function getErrorCode(): string|int;
}
