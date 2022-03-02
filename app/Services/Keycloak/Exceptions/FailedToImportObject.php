<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Utils\Processor\Processor;
use Throwable;

final class FailedToImportObject extends FailedToImport implements GenericException {
    public function __construct(Processor $processor, object $object, Throwable $previous = null) {
        parent::__construct($processor, $object, 'Failed to import object.', $previous);
    }
}
