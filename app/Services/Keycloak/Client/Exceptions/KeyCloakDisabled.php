<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function __;

class KeyCloakDisabled extends ClientException implements IteratorFatalError {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Keycloak client disabled', $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.disabled');
    }
}
