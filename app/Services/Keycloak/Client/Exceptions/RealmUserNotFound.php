<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use Throwable;

use function __;
use function sprintf;

class RealmUserNotFound extends ClientException {
    public function __construct(
        protected string $id,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Keycloak User `%s` not found.', $this->id), $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.realm_user_not_found');
    }
}
