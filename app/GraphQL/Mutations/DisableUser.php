<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeycloakUser;

class DisableUser {
    public function __construct(
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
        $user          = User::whereKey($args['input']['id'])->first();
        $user->enabled = false;

        // Keycloak
        $result = true;
        if ($user->type === UserType::keycloak()) {
            $result = $this->client->updateUser(
                $user->getKey(),
                new KeycloakUser(['enabled' => false]),
            );
        }
        return ['result' => $result && $user->save()];
    }
}