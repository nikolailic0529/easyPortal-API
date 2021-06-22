<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use Throwable;

use function __;

class UserAlreadyExists extends ClientException {

    public function __construct(string $username, Throwable $previous = null) {
        parent::__construct("Keycloak user exists exits with same username: {$username}", 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.user_already_exists');
    }
}
