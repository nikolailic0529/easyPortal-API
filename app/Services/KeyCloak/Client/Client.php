<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Models\Organization;
use App\Models\Role as RoleModel;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\KeyCloak\Client\Exceptions\InvalidSettingClientUuid;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakUnavailable;
use App\Services\KeyCloak\Client\Exceptions\RealmGroupUnknown;
use App\Services\KeyCloak\Client\Exceptions\RealmUserAlreadyExists;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound;
use App\Services\KeyCloak\Client\Exceptions\RequestFailed;
use App\Services\KeyCloak\Client\Exceptions\ServerError;
use App\Services\KeyCloak\Client\Types\Credential;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\Role;
use App\Services\KeyCloak\Client\Types\User;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

use function array_map;
use function http_build_query;
use function is_string;
use function rtrim;

class Client {
    public function __construct(
        protected Factory $client,
        protected Repository $config,
        protected Token $token,
    ) {
        // empty
    }

    // <editor-fold desc="endpoints">
    // =========================================================================
    public function getGroup(Organization|RoleModel|string $object): ?Group {
        // GET /{realm}/groups/{id}
        $id = null;

        if ($object instanceof Organization) {
            $id = $object->keycloak_group_id;
        } elseif ($object instanceof RoleModel) {
            $id = $object->getKey();
        } elseif (is_string($object)) {
            $id = $object;
        } else {
            // empty
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
        $this->call($endpoint, 'PUT', [
            'json' => $role->toArray(),
        ]);
    }

    public function deleteRoleByName(string $name): void {
        // PUT /{realm}/clients/{id}/roles/{role-name}
        $endpoint = "{$this->getClientUrl()}/roles/{$name}";
        $this->call($endpoint, 'DELETE');
    }

    public function createSubGroup(Organization $organization, string $name): Group {
        // POST /{realm}/groups/{id}/children
        if (!$organization->keycloak_group_id) {
            throw new RealmGroupUnknown();
        }

        $endpoint = "groups/{$organization->keycloak_group_id}/children";
        $input    = new Group(['name' => $name]);
        $result   = $this->call($endpoint, 'POST', ['json' => $input->toArray()]);
        $group    = new Group($result);

        return $group;
    }

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
     * @return array<\App\Services\KeyCloak\Client\Types\Role>
     */
    public function getGroupRoles(Group|RoleModel $group): array {
        // POST /{realm}/groups/{id}/role-mappings/clients/{client}
        $id       = $group instanceof Group ? $group->id : $group->getKey();
        $endpoint = "groups/{$id}/role-mappings/{$this->getClientUrl()}";
        $roles    = Role::make($this->call($endpoint));

        return $roles;
    }

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $roles
     */
    public function addRolesToGroup(Group|RoleModel $group, array $roles): void {
        // POST /{realm}/groups/{id}/role-mappings/clients/{client}
        $id       = $group instanceof Group ? $group->id : $group->getKey();
        $endpoint = "groups/{$id}/role-mappings/{$this->getClientUrl()}";

        $this->call($endpoint, 'POST', ['json' => $roles]);
    }

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $roles
     */
    public function setGroupRoles(Group|RoleModel $group, array $roles): bool {
        $keyBy    = static function (Role $role): string {
            return $role->id;
        };
        $roles    = (new Collection($roles))->keyBy($keyBy);
        $existing = (new Collection($this->getGroupRoles($group)))->keyBy($keyBy);
        $remove   = $existing->diffKeys($roles)->values()->all();
        $create   = $roles->diffKeys($existing)->values()->all();

        if ($remove) {
            $this->removeRolesFromGroup($group, $remove);
        }

        if ($create) {
            $this->addRolesToGroup($group, $create);
        }

        return true;
    }

    public function inviteUser(RoleModel $role, string $email): bool {
        // POST /{realm}/users
        $endpoint = 'users';

        // Get Group path
        $group = $this->getGroup($role);

        if (!$group) {
            throw new RealmGroupUnknown();
        }

        $input = new User([
            'email'         => $email,
            'groups'        => [$group->path],
            'enabled'       => false,
            'emailVerified' => false,
        ]);

        try {
            $this->call($endpoint, 'POST', [
                'json' => $input->toArray(),
            ]);
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_CONFLICT)) {
                throw new RealmUserAlreadyExists($email, $exception);
            }

            throw $exception;
        }

        return true;
    }

    public function getUserById(string $id): User {
        // GET /{realm}/users/{id}

        try {
            $result = $this->call("users/{$id}");
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_NOT_FOUND)) {
                throw new RealmUserNotFound($id, $exception);
            }

            throw $exception;
        }

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
    public function removeRolesFromGroup(Group|RoleModel $group, array $permissions): void {
        // DELETE /{realm}/groups/{id}/role-mappings/clients/{client}
        $id       = $group instanceof Group ? $group->id : $group->getKey();
        $endpoint = "groups/{$id}/role-mappings/{$this->getClientUrl()}";

        $this->call($endpoint, 'DELETE', ['json' => $permissions]);
    }

    public function getUserByEmail(string $email): ?User {
        // GET /{realm}/users?email={email}
        $endpoint = "users?email={$email}";
        $users    = $this->call($endpoint);
        if (empty($users)) {
            return null;
        }

        return new User($users[0]);
    }

    public function requestResetPassword(string $id): void {
        // PUT /{realm}/users/{id}/execute-actions-email
        $endpoint = "users/{$id}/execute-actions-email";
        $this->call($endpoint, 'PUT', ['json' => ['UPDATE_PASSWORD']]);
    }

    /**
     * @return array<\App\Services\KeyCloak\Client\Types\Group>
     */
    public function getUserGroups(string $id): array {
        // GET /{realm}/users/{id}/groups
        try {
            $endpoint = "users/{$id}/groups";
            $groups   = $this->call($endpoint);
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_NOT_FOUND)) {
                throw new RealmUserNotFound($id, $exception);
            }

            throw $exception;
        }

        return array_map(static function ($group) {
            return new Group($group);
        }, $groups);
    }

    public function addUserToGroup(string $userId, string $groupId): void {
        // PUT /{realm}/users/{id}/groups/{groupId}
        $endpoint = "users/{$userId}/groups/{$groupId}";
        $this->call($endpoint, 'PUT');
    }

    public function resetPassword(string $id, string $password): bool {
        // PUT /{realm}/users/{id}/reset-password
        $endpoint = "users/{$id}/reset-password";
        // Create new credentials
        $credential = new Credential([
            'type'      => 'password',
            'temporary' => false,
            'value'     => $password,
        ]);
        $this->call($endpoint, 'PUT', [
            'json' => $credential->toArray(),
        ]);

        return true;
    }

    public function updateUserEmail(string $id, string $email): void {
        // PUT /{realm}/users/{id}
        try {
            $this->call("users/{$id}", 'PUT', ['json' => ['email' => $email]]);
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_CONFLICT)) {
                throw new RealmUserAlreadyExists($email, $exception);
            }

            throw $exception;
        }
    }

    /**
     * @return array<\App\Services\KeyCloak\Client\Types\User>
     */
    public function getUsers(int $limit, int $offset): array {
        $keycloak = rtrim($this->config->get('ep.keycloak.url'), '/');
        $realm    = $this->config->get('ep.keycloak.realm');
        $baseUrl  = "{$keycloak}/auth/realms/{$realm}/custom";
        $params   = http_build_query(['offset' => $offset, 'limit' => $limit]);
        $endpoint = "users?{$params}";

        $result = $this->call($endpoint, 'GET', [], $baseUrl);
        $result = array_map(static function ($item) {
            return new User($item);
        }, $result);

        return $result;
    }

    public function usersCount(): int {
        // GET /{realm}/users/count
        $endpoint = 'users/count';
        $result   = $this->call($endpoint);

        return $result;
    }

    public function getUsersIterator(): QueryIterator {
        return new UsersIterator($this);
    }

    public function removeUserFromGroup(string $userId, string $groupId): void {
        // DELETE /{realm}/users/{id}/groups/{groupId}
        $endpoint = "users/{$userId}/groups/{$groupId}";
        $this->call($endpoint, 'DELETE');
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
     * @param array<string,mixed> $data
     */
    protected function call(string $endpoint, string $method = 'GET', array $data = [], string $baseUrl = null): mixed {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new KeyCloakDisabled();
        }

        // Prepare
        $timeout   = $this->config->get('ep.keycloak.timeout') ?: 5 * 60;
        $baseUrl ??= $this->getBaseUrl();
        $headers   = [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$this->token->getAccessToken()}",
        ];
        $request   = $this->client
            ->baseUrl($baseUrl)
            ->timeout($timeout)
            ->withHeaders($headers)
            ->asJson();

        // Call
        $json = null;

        try {
            $json = $request->send($method, $endpoint, $data)->throw()->json();
        } catch (ConnectionException $exception) {
            throw new KeyCloakUnavailable($exception);
        } catch (Exception $exception) {
            if ($exception instanceof RequestException && $exception->getCode() >= 500 && $exception->getCode() < 600) {
                throw new ServerError("{$baseUrl}/{$endpoint}", $method, $data, $exception);
            } else {
                throw new RequestFailed("{$baseUrl}/{$endpoint}", $method, $data, $exception);
            }
        }

        // Return
        return $json;
    }

    protected function getClientUrl(): string {
        $uuid = (string) $this->config->get('ep.keycloak.client_uuid');

        if (!$uuid) {
            throw new InvalidSettingClientUuid();
        }

        return "clients/{$uuid}";
    }
    // </editor-fold>
}
