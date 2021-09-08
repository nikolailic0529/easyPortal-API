<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use Throwable;

use function __;

class InvalidSettingClientUuid extends ClientException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid keycloak client UUID.', $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.invalid_setting_client_uuid');
    }
}
