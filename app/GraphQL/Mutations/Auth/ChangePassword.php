<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Enums\UserType;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\UserProvider;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;

class ChangePassword {
    public function __construct(
        protected AuthManager $auth,
        protected UserProvider $provider,
        protected Client $client,
        protected PasswordBrokerFactory $password,
        protected ResetPassword $resetPassword,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        /** @var \App\Models\User $user */
        $user = $this->auth->user();
        if ($user->type === UserType::keycloak()) {
            $this->client->resetPassword($user->id, $args['input']['password']);
            return ['result' => true];
        } elseif ($user->type === UserType::local()) {
            $valid = $this->provider->validateCredentials($user, [
                UserProvider::CREDENTIAL_PASSWORD => $args['input']['current_password'],
                UserProvider::CREDENTIAL_EMAIL    => $user->email,
            ]);

            if (!$valid) {
                throw new ChangePasswordInvalidCurrentPassword();
            }

            $resetPassword = $this->resetPassword;
            return $resetPassword(null, [
                'input' => [
                    'email'    => $user->email,
                    'password' => $args['input']['password'],
                    'token'    => $this->password->broker()->getRepository()->create($user),
                ],
            ]);
        }
    }
}
