<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use Throwable;

use function __;

class EndpointException extends ClientException {
    public function __construct(string $endpoint, Throwable $previous = null) {
        parent::__construct("Endpoint failed response {$endpoint}", 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.endpoint_failure');
    }
}
