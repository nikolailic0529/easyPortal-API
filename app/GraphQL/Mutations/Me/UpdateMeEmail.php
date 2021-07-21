<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Auth\AuthManager;

class UpdateMeEmail {
    public function __construct(
        protected AuthManager $auth,
        protected Client $client,
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
        // Possible?
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $this->auth->user();
        if (!($user instanceof User)) {
            return [
                'result' => false,
            ];
        }
        $email  = $args['input']['email'];
        $result = false;

        switch ($user->type) {
            case UserType::local():
                if (User::query()->where('email', '=', $email)->exists()) {
                    throw new UpdateMeEmailUserAlreadyExists($email);
                }
                $user->email = $email;
                $result      = $user->save();
                break;
            case UserType::keycloak():
                $this->client->updateUserEmail($user->getKey(), $email);
                $result = true;
                break;
            default:
                // empty
                break;
        }
        return ['result' => $result ];
    }
}
