<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use App\Models\Enums\UserType;
use App\Models\User;
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

    /**
     * @inheritDoc
     */
    public function getUser(array $credentials): mixed {
        // fixme(passwords): Only local users supported.
        $user = parent::getUser($credentials);

        if ($user instanceof User && $user->type !== UserType::local()) {
            $user = null;
        }

        return $user;
    }
}
