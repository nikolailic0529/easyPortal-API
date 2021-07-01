<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use App\Services\KeyCloak\UserProvider;
use Illuminate\Auth\Passwords\PasswordBroker as IlluminatePasswordBroker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;


class PasswordBroker extends IlluminatePasswordBroker {
    /**
     * Constant representing using the same password.
     *
     */
    public const INVALID_PASSWORD_SAMEPASSWORD = 'passwords.same_password';

    public function __construct(
        TokenRepositoryInterface $tokens,
        UserProvider $users,
        protected Hasher $hasher,
    ) {
        $this->users  = $users;
        $this->tokens = $tokens;
    }

    /**
     * @inheritdoc
     */
    public function validateReset(array $credentials) {
        $user = parent::validateReset($credentials);

        if ($user instanceof Authenticatable && $this->users->validateCredentials($user, $credentials)) {
            return static::INVALID_PASSWORD_SAMEPASSWORD;
        }

        return $user;
    }
}
