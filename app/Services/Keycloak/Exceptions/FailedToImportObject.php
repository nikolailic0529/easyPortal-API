<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Throwable;

final class FailedToImportObject extends FailedToImport implements GenericException {
    /**
     * @param Processor<mixed,mixed,State> $processor
     */
    public function __construct(Processor $processor, object $object, Throwable $previous = null) {
        parent::__construct($processor, $object, 'Failed to import object.', $previous);
    }
}
