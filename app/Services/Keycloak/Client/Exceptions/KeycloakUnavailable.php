<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use App\Exceptions\Contracts\ExternalException;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function __;

class KeycloakUnavailable extends ClientException implements ExternalException, IteratorFatalError {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Keycloak unavailable.', $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.unavailable');
    }
}
