<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;

class UpdateMeEmail {
    public function __construct(
        protected AuthManager $auth,
        protected Client $client,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        // Possible?
        /** @var Authenticatable $user */
        $user = $this->auth->user();
        if (!($user instanceof User)) {
            return [
                'result' => false,
            ];
        }
        $email = $args['input']['email'];

        if ($user->type === UserType::keycloak()) {
            $this->client->updateUserEmail($user->getKey(), $email);
        }

        $user->email = $email;

        return ['result' => $user->save()];
    }
}
