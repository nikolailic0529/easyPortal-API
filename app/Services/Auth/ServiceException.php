<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Exceptions\ApplicationException;
use Throwable;

abstract class ServiceException extends ApplicationException {
    protected function __construct(string $message, Throwable $previous = null) {
        parent::__construct($message, $previous);

        $this->setChannel(Service::class);
    }
}
