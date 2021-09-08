<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use Throwable;

use function __;

class RealmGroupUnknown extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Group is unknown.', $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.realm_group_unknown');
    }
}
