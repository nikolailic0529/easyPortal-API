<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Search\Processor\Processor;
use App\Services\Search\ServiceException;
use Throwable;

use function sprintf;

class IndexError extends ServiceException implements GenericException {
    public function __construct(
        protected Processor $processor,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('`%s` error.', $this->processor::class), $previous);
    }
}
