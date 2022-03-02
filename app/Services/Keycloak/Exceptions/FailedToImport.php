<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Services\Keycloak\ServiceException;
use App\Utils\Processor\Processor;
use Throwable;

abstract class FailedToImport extends ServiceException {
    public function __construct(
        protected Processor $processor,
        protected object $object,
        string $message,
        Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);

        $this->setContext([
            'processor' => $this->processor::class,
            'object'    => $this->object,
        ]);
    }
}
