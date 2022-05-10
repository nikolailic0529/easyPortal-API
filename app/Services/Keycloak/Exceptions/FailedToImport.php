<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Services\Keycloak\ServiceException;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Throwable;

abstract class FailedToImport extends ServiceException {
    /**
     * @param Processor<mixed,mixed,State> $processor
     */
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
