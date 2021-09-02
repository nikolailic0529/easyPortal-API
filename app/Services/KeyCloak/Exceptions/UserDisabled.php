<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Exceptions\HasErrorCode;
use Throwable;

use function __;
use function sprintf;

class UserDisabled extends KeyCloakException {
    use HasErrorCode;

    public function __construct(string $id, Throwable $previous = null) {
        parent::__construct(sprintf(
            'User `%s` is disabled.',
            $id,
        ), 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('auth.user_disabled');
    }
}
