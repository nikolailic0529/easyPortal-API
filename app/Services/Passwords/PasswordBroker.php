<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use App\Models\User;
use App\Services\KeyCloak\UserProvider;
use Illuminate\Auth\Passwords\PasswordBroker as IlluminatePasswordBroker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Hashing\Hasher;


class PasswordBroker extends IlluminatePasswordBroker {
    /**
     * ... message ...
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

        if ($user instanceof User && $this->hasher->check($credentials['password'], $user->password)) {
            return static::INVALID_PASSWORD_SAMEPASSWORD;
        }

        return $user;
    }
}
