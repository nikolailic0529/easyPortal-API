<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Models\Organization;
use App\Models\Role as RoleModel;
use App\Services\KeyCloak\Client\Exceptions\EndpointException;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakClient;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakGroup;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\Role;
use App\Services\KeyCloak\Client\Types\User;
use Closure;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\HttpFoundation\Response;

use function array_map;
use function rtrim;

class Client {

    public function __construct(
        protected Factory $client,
        protected Repository $config,
        protected ExceptionHandler $handler,
        protected Token $token,
    ) {
        // empty
    }

    // <editor-fold desc="endpoints">
    // =========================================================================
    /**
     * @return array<\App\Services\KeyCloak\Client\Types\User>
     */
    public function users(Organization $organization): array {
        // GET /{realm}/groups/{id}/members
        if (!$organization->keycloak_group_id) {
            throw new InvalidKeyCloakGroup();
        }

        $endpoint = "groups/{$organization->keycloak_group_id}/members";
        $result   = $this->call($endpoint);
        $result   = array_map(static function ($item) {
            return new User($item);
        }, $result);

        return $result;
    }

    public function getGroup(Organization|RoleModel $object): ?Group {
        // GET /{realm}/groups/{id}
        $id = null;

        if ($object instanceof Organization) {
            $id = $object->keycloak_group_id;
        } elseif ($object instanceof RoleModel) {
            $id = $object->getKey();
        }

        if (!$id) {
            return null;
        }

        $endpoint = "groups/{$id}";
        $result   = $this->call($endpoint);
        $result   = new Group($result);

        return $result;
    }

    /**
     * @return array<\App\Services\KeyCloak\Client\Types\Role>
     */
    public function getRoles(): array {
        // GET /{realm}/clients/{id}/roles
        $endpoint = "{$this->getClientUrl()}/roles";
        $result   = $this->call($endpoint);
        $result   = array_map(static function ($item) {
            return new Role($item);
        }, $result);

        return $result;
    }

    public function createRole(Role $role): Role {
        // POST /{realm}/clients/{id}/roles
        $endpoint = "{$this->getClientUrl()}/roles";
        $this->call($endpoint, 'POST', ['json' => $role->toArray()]);
        return $this->getRoleByName($role->name);
    }

    public function getRoleByName(string $name): Role {
        // GET /{realm}/clients/{id}/roles/{role-name}
        $endpoint = "{$this->getClientUrl()}/roles/{$name}";
        $result   = $this->call($endpoint, 'GET');
        return new Role($result);
    }

    public function updateRoleByName(string $name, Role $role): void {
        // PUT /{realm}/clients/{id}/roles/{role-name}
        $endpoint = "{$this->getClientUrl()}/roles/{$name}";
        $this->call(
            $endpoint,
            'PUT',
            ['json' => $role->toArray()],
        );
    }
    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $roles
     */
    public function createSubGroup(Organization $organization, string $name, array $roles = []): Group {
        // POST /{realm}/groups/{id}/children
        if (!$organization->keycloak_group_id) {
            throw new InvalidKeyCloakGroup();
        }

        $endpoint = "groups/{$organization->keycloak_group_id}/children";
        $input    = new Group(['name' => $name]);
        $result   = $this->call($endpoint, 'POST', ['json' => $input->toArray()]);
        $group    = new Group($result);

        return $group;
    }

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $roles
     */
    public function editSubGroup(RoleModel $role, string $name): void {
        // PUT /{realm}/groups/{id}
        $endpoint = "groups/{$role->id}";
        $input    = new Group(['name' => $name]);
        $this->call($endpoint, 'PUT', ['json' => $input->toArray()]);
    }

    public function deleteGroup(RoleModel $role): void {
        // DELETE /{realm}/groups/{id}
        $endpoint = "groups/{$role->getKey()}";
        $this->call($endpoint, 'DELETE');
    }

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $permissions
     */
    public function addRolesToGroup(RoleModel $role, array $permissions): void {
        // POST /{realm}/groups/{id}/role-mappings/clients/{client}
        $endpoint = "groups/{$role->id}/role-mappings/{$this->getClientUrl()}";
        $this->call($endpoint, 'POST', ['json' => $permissions]);
    }

    /**
     * @return array<\App\Services\KeyCloak\Client\Types\Role>
     */
    public function roles(): array {
        // GET /{realm}/clients/{id}/roles
        $endpoint = "{$this->getClientUrl()}/roles";
        $result   = $this->call($endpoint);
        $result   = array_map(static function ($item) {
            return new Role($item);
        }, $result);

        return $result;
    }

    public function inviteUser(RoleModel $role, string $email): bool {
        // POST /{realm}/users
        $endpoint = 'users';

        // Get Group path
        $group = $this->getGroup($role);

        if (!$group) {
            throw new InvalidKeyCloakGroup();
        }

        $input        = new User([
            'email'         => $email,
            'groups'        => [$group->path],
            'enabled'       => false,
            'emailVerified' => false,
            'attributes'    => [
                'invited'                       => [1],
                'added_password_through_invite' => [0],
            ],
        ]);
        $errorHandler = function (Exception $exception) use ($endpoint, $email): void {
            if ($exception instanceof RequestException) {
                if ($exception->getCode() === Response::HTTP_CONFLICT) {
                    throw new UserAlreadyExists($email);
                }
            }
            $this->endpointException($exception, $endpoint);
        };


        $this->call($endpoint, 'POST', [
            'json' => $input->toArray(),
        ], $errorHandler);

        return true;
    }

    public function getUserById(string $id): User {
        // GET /{realm}/users/{id}
        $endpoint = "users/{$id}";
        $result   = $this->call($endpoint);
        return new User($result);
    }

    public function updateUser(string $id, User $user): bool {
        // PUT /{realm}/users/{id}
        $endpoint = "users/{$id}";

        $this->call($endpoint, 'PUT', ['json' => $user->toArray()]);

        return true;
    }

    /**
     * @param array<string> $permissions
     */
    public function removeRolesFromGroup(RoleModel $role, array $permissions): void {
        // DELETE /{realm}/groups/{id}/role-mappings/clients/{client}
        $endpoint = "groups/{$role->id}/role-mappings/{$this->getClientUrl()}";
        $this->call($endpoint, 'DELETE', ['json' => $permissions]);
    }

    public function getUserByEmail(string $email): ?User {
        // GET /{realm}/users?email={email}
        $endpoint = "users?email={$email}";
        $users    = $this->call($endpoint, 'GET');
        if (empty($users)) {
            return null;
        }
        return new User($users[0]);
    }
    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    public function getBaseUrl(): string {
        $keycloak = rtrim($this->config->get('ep.keycloak.url'), '/');
        $realm    = $this->config->get('ep.keycloak.realm');

        return "{$keycloak}/auth/admin/realms/{$realm}";
    }

    protected function isEnabled(): bool {
        return $this->config->get('ep.keycloak.url')
            && $this->config->get('ep.keycloak.client_id')
            && $this->config->get('ep.keycloak.client_secret');
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function call(
        string $endpoint,
        string $method = 'GET',
        array $options = [],
        Closure $errorHandler = null,
    ): mixed {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new KeyCloakDisabled();
        }

        $timeout     = $this->config->get('ep.keycloak.timeout') ?: 5 * 60;
        $accessToken = $this->token->getAccessToken();
        $headers     = [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$accessToken}",
        ];

        $request = $this->client
            ->baseUrl($this->getBaseUrl())
            ->timeout($timeout);

        try {
            $response = $request
                ->withHeaders($headers)
                ->asJson()
                ->send($method, $endpoint, $options);
            $response->throw();
        } catch (Exception $exception) {
            if ($errorHandler) {
                return $errorHandler($exception);
            } else {
                $this->endpointException($exception, $endpoint);
            }
        }

        return $response->json();
    }

    protected function endpointException(Exception $exception, string $endpoint): void {
        $error = new EndpointException($endpoint, $exception);
        $this->handler->report($error);
        throw $error;
    }

    protected function getClientUrl(): string {
        $clientId = (string) $this->config->get('ep.keycloak.client_uuid');
        if (!$clientId) {
            throw new InvalidKeyCloakClient();
        }
        return "clients/{$clientId}";
    }
    // </editor-fold>
}
