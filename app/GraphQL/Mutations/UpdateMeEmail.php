<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Enums\UserType;
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
        /** @var \App\Models\User $user */
        $user  = $this->auth->user();
        $email = $args['input']['email'];

        if ($user->type === UserType::keycloak()) {
            $this->client->updateUserEmail($user->getKey(), $email);
        }

        $user->email = $email;
        return ['result' => $user->save() ];
    }
}
