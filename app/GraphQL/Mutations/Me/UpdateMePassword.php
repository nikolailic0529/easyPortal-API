<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\GraphQL\Mutations\Auth\ResetPassword;
use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\UserProvider;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;

class UpdateMePassword {
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
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        // Possible?
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $this->auth->user();

        if (!($user instanceof User)) {
            return [
                'result' => false,
            ];
        }

        // Reset
        $result = false;

        switch ($user->type) {
            case UserType::keycloak():
                $result = $this->client->resetPassword($user->getKey(), $args['input']['password']);
                break;
            case UserType::local():
                $valid = $this->provider->validateCredentials($user, [
                    UserProvider::CREDENTIAL_PASSWORD => $args['input']['current_password'],
                    UserProvider::CREDENTIAL_EMAIL    => $user->email,
                ]);

                if (!$valid) {
                    throw new UpdateMePasswordInvalidCurrentPassword();
                }

                $result = ($this->resetPassword)(null, [
                    'input' => [
                        'email'    => $user->email,
                        'password' => $args['input']['password'],
                        'token'    => $this->password->broker()->getRepository()->create($user),
                    ],
                ])['result'];

                break;
            default:
                // empty
                break;
        }

        // Return
        return [
            'result' => $result,
        ];
    }
}
