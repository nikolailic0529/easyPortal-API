<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use Throwable;

use function trans;

class RealmRoleAlreadyExists extends ClientException {
    public function __construct(
        protected string $name,
        Throwable $previous = null,
    ) {
        parent::__construct("Keycloak role `{$this->name}` already exists.", $previous);
    }

    public function getErrorMessage(): string {
        return trans('keycloak.client.realm_role_already_exists');
    }
}
