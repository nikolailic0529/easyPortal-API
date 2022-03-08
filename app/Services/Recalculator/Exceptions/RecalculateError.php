<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Recalculator\Processor\Processor;
use App\Services\Recalculator\ServiceException;
use Throwable;

use function sprintf;

final class RecalculateError extends ServiceException implements GenericException {
    public function __construct(
        protected Processor $processor,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('`%s` error.', $this->processor::class), $previous);
    }
}
