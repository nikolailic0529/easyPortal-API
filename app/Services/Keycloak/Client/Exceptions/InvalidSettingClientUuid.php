<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Exceptions;

use Throwable;

use function trans;

class InvalidSettingClientUuid extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid keycloak client UUID.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('keycloak.client.invalid_setting_client_uuid');
    }
}
