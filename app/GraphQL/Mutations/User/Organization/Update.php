<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User\Organization;

use App\Models\OrganizationUser;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RequestFailed;
use Symfony\Component\HttpFoundation\Response;

class Update {
    public function __construct(
        protected Client $client,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(OrganizationUser $user, array $args): bool {
        return $this->update($user, UpdateInput::make($args['input']));
    }

    public function update(OrganizationUser $user, UpdateInput $input): bool {
        // Model
        $user           = $user->forceFill($input->getProperties());
        $previousRoleId = $user->getOriginal('role_id');

        if (!$user->save()) {
            return false;
        }

        // KeyCloak
        $keycloakUser = $this->client->getUserById($user->user_id);
        $result       = true;

        if ($user->role_id) {
            $result = $this->client->addUserToGroup($keycloakUser, $user->role_id);
        }

        if ($previousRoleId) {
            try {
                $this->client->removeUserFromGroup($keycloakUser, $previousRoleId);
            } catch (RequestFailed $exception) {
                if (!$exception->isHttpError(Response::HTTP_NOT_FOUND)) {
                    throw $exception;
                }
            }
        }

        return $result;
    }
}
