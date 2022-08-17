<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User\Organization;

use App\Models\OrganizationUser;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Exceptions\RequestFailed;
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
        return $this->update($user, new UpdateInput($args['input']));
    }

    public function update(OrganizationUser $user, UpdateInput $input): bool {
        // Model
        $previousRoleId = $user->role_id;
        $user           = $user->forceFill($input->getProperties());

        if ($user->isClean()) {
            return true;
        }

        if (!$user->save()) {
            return false;
        }

        // Keycloak
        $keycloakUser = $this->client->getUserById($user->user_id);
        $result       = true;

        if ($user->role_id) {
            $result = $this->client->addUserToGroup($keycloakUser, $user->role_id);
        }

        if ($previousRoleId && $previousRoleId !== $user->role_id) {
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
