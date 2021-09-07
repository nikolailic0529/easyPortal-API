<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

abstract class InvalidData extends ServiceException {
    protected function __construct(string $message, Throwable $previous = null) {
        parent::__construct($message, $previous);

        $this->setLevel(LogLevel::NOTICE);
    }
}
