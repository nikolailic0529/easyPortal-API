<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Keycloak\ServiceException;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use Throwable;

use function __;

class KeycloakDisabled extends ServiceException implements IteratorFatalError, TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Keycloak client disabled', $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.disabled');
    }
}
