<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Keycloak\ServiceException;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Throwable;

use function sprintf;

final class ImportError extends ServiceException implements GenericException {
    /**
     * @param Processor<mixed,mixed,State> $processor
     */
    public function __construct(
        protected Processor $processor,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('`%s` error.', $this->processor::class), $previous);
    }
}
