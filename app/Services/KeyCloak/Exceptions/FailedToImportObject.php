<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\KeyCloak\ServiceException;
use App\Utils\Processor\Processor;
use Throwable;

final class FailedToImportObject extends ServiceException implements GenericException {
    public function __construct(
        protected Processor $processor,
        protected object $object,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to import object.', $previous);

        $this->setContext([
            'processor' => $this->processor::class,
            'object'    => $this->object,
        ]);
    }
}
