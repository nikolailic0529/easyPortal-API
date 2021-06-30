<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use App\Services\KeyCloak\UserProvider;
use Illuminate\Auth\Passwords\PasswordBroker as IlluminatePasswordBroker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Hashing\Hasher;

use function is_null;

class PasswordBroker extends IlluminatePasswordBroker {
    /**
     * Constant representing an invalid password.
     *
     */
    public const INVALID_PASSWORD = 'passwords.password';

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
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if (! $this->tokens->exists($user, $credentials['token'])) {
            return static::INVALID_TOKEN;
        }

        if ($user && $this->hasher->check($credentials['password'], $user->password)) {
            return static::INVALID_PASSWORD;
        }

        return $user;
    }
}
