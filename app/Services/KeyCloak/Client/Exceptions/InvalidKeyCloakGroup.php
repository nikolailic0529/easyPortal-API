<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use Throwable;

use function __;

class InvalidKeyCloakGroup extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid keycloak group', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.invalid_group');
    }
}