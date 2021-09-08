<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use Throwable;

use function __;

class RealmUserAlreadyExists extends ClientException {
    public function __construct(
        protected string $username,
        Throwable $previous = null,
    ) {
        parent::__construct("Keycloak User `{$this->username}` exists.", $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.realm_user_already_exists');
    }
}
