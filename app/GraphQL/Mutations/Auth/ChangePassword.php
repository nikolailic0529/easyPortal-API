<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\UserProvider;
use App\Services\Passwords\PasswordBroker;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Hashing\Hasher;

class ChangePassword {
    public function __construct(
        protected AuthManager $auth,
        protected UserProvider $provider,
        protected Hasher $hasher,
        protected Client $client,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args) {
        /** @var \App\Models\User $user */
        $user  = $this->auth->user();
        if ($user->type === UserType::keycloak()) {
            
        } else if ($user->type === UserType::local()) {
            $valid = $this->provider->validateCredentials($user, [
                UserProvider::CREDENTIAL_PASSWORD => $args['input']['current_password'],
                UserProvider::CREDENTIAL_EMAIL    => $args['input']['email'],
            ]);

            if (!$valid) {
                throw new ChangePasswordInvalidCurrentPassword();
            }

            $user->password = $this->hasher->make($args['input']['password']);
            $result = $user->save();
        }

        return ['result' => $result];
    }
}
