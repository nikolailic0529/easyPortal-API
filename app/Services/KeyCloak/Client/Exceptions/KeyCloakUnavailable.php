<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use App\Exceptions\ExternalException;
use Throwable;

use function __;

class KeyCloakUnavailable extends ClientException implements ExternalException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('KeyCloak unavailable.', $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.unavailable');
    }
}
