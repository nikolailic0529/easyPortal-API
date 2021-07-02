<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use Illuminate\Auth\Passwords\PasswordBroker as IlluminatePasswordBroker;
use Illuminate\Contracts\Auth\Authenticatable;


class PasswordBroker extends IlluminatePasswordBroker {
    /**
     * Constant representing using the same password.
     */
    public const INVALID_PASSWORD_SAMEPASSWORD = 'passwords.same_password';

    /**
     * @inheritdoc
     */
    public function validateReset(array $credentials): mixed {
        $user = parent::validateReset($credentials);

        if ($user instanceof Authenticatable && $this->users->validateCredentials($user, $credentials)) {
            return static::INVALID_PASSWORD_SAMEPASSWORD;
        }

        return $user;
    }
}
