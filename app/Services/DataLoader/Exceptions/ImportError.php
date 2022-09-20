<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\ServiceException;
use Throwable;

use function sprintf;

final class ImportError extends ServiceException implements GenericException {
    public function __construct(
        protected Importer $importer,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('`%s` error.', $this->importer::class), $previous);
    }
}
