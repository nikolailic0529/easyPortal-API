<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use Throwable;

use function trans;

class RealmGroupUnknown extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Group is unknown.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('keycloak.client.realm_group_unknown');
    }
}
