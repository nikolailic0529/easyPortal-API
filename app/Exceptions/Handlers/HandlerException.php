<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use App\Exceptions\ApplicationException;
use Psr\Log\LogLevel;
use Throwable;

use function sprintf;

class HandlerException extends ApplicationException {
    public function __construct(object $handler, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Log handler `%s` failed to handle record(s).',
            $handler::class,
        ), $previous);

        $this->setChannel('emergency');
        $this->setLevel(LogLevel::WARNING);
    }
}
