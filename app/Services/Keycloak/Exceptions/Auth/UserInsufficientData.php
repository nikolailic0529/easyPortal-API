<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions\Auth;

use App\Models\User;
use App\Services\Auth\Exceptions\AuthException;
use Throwable;

use function implode;
use function sprintf;
use function trans;

class UserInsufficientData extends AuthException {
    /**
     * @param array<string> $missed
     */
    public function __construct(
        protected User $user,
        protected array $missed,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Insufficient data to create/update User `%s`, missed: `%s`.',
            $this->user->getKey(),
            implode('`, `', $this->missed),
        ), $previous);
    }

    public function getErrorMessage(): string {
        return trans('keycloak.auth.insufficient_data');
    }
}
