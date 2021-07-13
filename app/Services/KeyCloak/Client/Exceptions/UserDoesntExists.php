<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Exceptions;

use App\Exceptions\HasErrorCode;
use Throwable;

use function __;

class UserDoesntExists extends ClientException {
    use HasErrorCode;

    public function __construct(Throwable $previous = null) {
        parent::__construct("Keycloak user doesn't exists", 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('keycloak.client.user_doesnt_exists');
    }
}
