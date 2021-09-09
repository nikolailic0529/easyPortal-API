<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\ApplicationMessage;
use App\Services\DataLoader\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

abstract class FailedToProcessObject extends ServiceException implements ApplicationMessage {
    protected function __construct(string $message, Throwable $previous = null) {
        parent::__construct($message, $previous);

        $this->setLevel(LogLevel::WARNING);
    }
}
