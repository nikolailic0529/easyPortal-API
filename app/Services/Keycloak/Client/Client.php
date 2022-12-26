<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Keycloak\Client\Exceptions\InvalidSettingClientUuid;
use App\Services\Keycloak\Client\Exceptions\KeycloakUnavailable;
use App\Services\Keycloak\Client\Exceptions\RealmGroupUnknown;
use App\Services\Keycloak\Client\Exceptions\RealmRoleAlreadyExists;
use App\Services\Keycloak\Client\Exceptions\RealmUserAlreadyExists;
use App\Services\Keycloak\Client\Exceptions\RealmUserNotFound;
use App\Services\Keycloak\Client\Exceptions\RequestFailed;
use App\Services\Keycloak\Client\Exceptions\ServerError;
use App\Services\Keycloak\Client\Types\Credential as KeycloakCredential;
use App\Services\Keycloak\Client\Types\Group as KeycloakGroup;
use App\Services\Keycloak\Client\Types\Role as KeycloakRole;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use App\Services\Keycloak\Exceptions\KeycloakDisabled;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\OffsetBasedObjectIterator;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as IlluminateResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

use function array_map;
use function assert;
use function http_build_query;
use function is_array;
use function is_string;
use function mb_strtolower;
use function rtrim;

class Client {
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected Factory $client,
        protected Repository $config,
        protected Token $token,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }
    // </editor-fold>

    // <editor-fold desc="Groups">
    // =========================================================================
    public function getGroup(Organization|Role|string $object): ?KeycloakGroup {
        // GET /{realm}/groups/{id}
        $id = null;

        if ($object instanceof Organization) {
            $id = $object->keycloak_group_id;
        } elseif ($object instanceof Role) {
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
        $result   = new KeycloakGroup($result);

        return $result;
    }

    public function createGroup(Role $role): KeycloakGroup {
        // POST /{realm}/groups/{id}/children

        // Organization?
        $organization = $role->organization;

        if (!$organization->keycloak_group_id) {
            throw new RealmGroupUnknown();
        }

        // Exists?
        $id          = $role->getKey();
        $search      = mb_strtolower($role->name);
        $parent      = $this->getGroup($organization);
        $groupById   = null;
        $groupByName = null;

        foreach ($parent->subGroups as $child) {
            if ($id === $child->id) {
                $groupById = $child;
            } elseif (mb_strtolower($child->name) === $search) {
                $groupByName = $child;
                break;
            } else {
                // empty
            }
        }

        if ($groupById && !$groupByName) {
            return $groupById;
        }

        if ($groupByName && !$role->exists) {
            return $groupByName;
        }

        // Role names are case-sensitive on Keycloak, but case-insensitive in
        // our app. So if another role is found we use its name to get
        // "conflict".
        try {
            $name     = $groupByName ? $groupByName->name : $role->name;
            $endpoint = "groups/{$organization->keycloak_group_id}/children";
            $input    = new KeycloakGroup([
                'name' => $name,
            ]);
            $result   = $this->call($endpoint, 'POST', [
                'json' => $input->toArray(),
            ]);
            $group    = new KeycloakGroup($result);
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_CONFLICT)) {
                throw new RealmRoleAlreadyExists($name, $exception);
            }

            throw $exception;
        }

        return $group;
    }

    public function updateGroup(KeycloakGroup|Role $group, string $name): bool {
        // PUT /{realm}/groups/{id}
        $id    = $group instanceof KeycloakGroup ? $group->id : $group->getKey();
        $input = new KeycloakGroup(['name' => $name]);

        $this->call("groups/{$id}", 'PUT', ['json' => $input->toArray()]);

        return true;
    }

    public function deleteGroup(KeycloakGroup|Role $group): bool {
        // DELETE /{realm}/groups/{id}
        $id     = $group instanceof KeycloakGroup ? $group->id : $group->getKey();
        $result = false;

        try {
            $this->call("groups/{$id}", 'DELETE');

            $result = true;
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_NOT_FOUND)) {
                $result = true;
            } else {
                throw $exception;
            }
        }

        return $result;
    }

    // <editor-fold desc="Roles">
    // -------------------------------------------------------------------------
    /**
     * @return array<KeycloakRole>
     */
    public function getGroupRoles(KeycloakGroup|Role $group): array {
        // POST /{realm}/groups/{id}/role-mappings/clients/{client}
        $id       = $group instanceof KeycloakGroup ? $group->id : $group->getKey();
        $endpoint = "groups/{$id}/role-mappings/{$this->getClientUrl()}";
        $roles    = KeycloakRole::make($this->call($endpoint));

        return $roles;
    }

    /**
     * @param array<KeycloakRole|Permission> $roles
     */
    public function createGroupRoles(KeycloakGroup|Role $group, array $roles): bool {
        // POST /{realm}/groups/{id}/role-mappings/clients/{client}
        $id       = $group instanceof KeycloakGroup ? $group->id : $group->getKey();
        $roles    = $this->toRoles($roles)->toArray();
        $endpoint = "groups/{$id}/role-mappings/{$this->getClientUrl()}";

        $this->call($endpoint, 'POST', ['json' => $roles]);

        return true;
    }

    /**
     * @param array<KeycloakRole|Permission> $roles
     */
    public function updateGroupRoles(KeycloakGroup|Role $group, array $roles): bool {
        $keyBy    = static function (KeycloakRole $role): string {
            return $role->id;
        };
        $roles    = $this->toRoles($roles)->keyBy($keyBy);
        $existing = (new Collection($this->getGroupRoles($group)))->keyBy($keyBy);
        $remove   = $existing->diffKeys($roles)->values()->all();
        $create   = $roles->diffKeys($existing)->values()->all();

        if ($remove) {
            $this->deleteGroupRoles($group, $remove);
        }

        if ($create) {
            $this->createGroupRoles($group, $create);
        }

        return true;
    }

    /**
     * @param array<KeycloakRole|Permission> $roles
     */
    public function deleteGroupRoles(KeycloakGroup|Role $group, array $roles): bool {
        // DELETE /{realm}/groups/{id}/role-mappings/clients/{client}
        $id       = $group instanceof KeycloakGroup ? $group->id : $group->getKey();
        $roles    = $this->toRoles($roles)->toArray();
        $endpoint = "groups/{$id}/role-mappings/{$this->getClientUrl()}";

        $this->call($endpoint, 'DELETE', ['json' => $roles]);

        return true;
    }
    //</editor-fold>
    //</editor-fold>

    // <editor-fold desc="Roles">
    // =========================================================================
    /**
     * @return array<KeycloakRole>
     */
    public function getRoles(): array {
        // GET /{realm}/clients/{id}/roles
        $endpoint = "{$this->getClientUrl()}/roles";
        $result   = KeycloakRole::make($this->call($endpoint));

        return $result;
    }

    public function getRole(string $name): KeycloakRole {
        // GET /{realm}/clients/{id}/roles/{role-name}
        $endpoint = "{$this->getClientUrl()}/roles/{$name}";
        $result   = $this->call($endpoint, 'GET');

        return new KeycloakRole($result);
    }

    public function createRole(KeycloakRole $role): KeycloakRole {
        // POST /{realm}/clients/{id}/roles
        $endpoint = "{$this->getClientUrl()}/roles";
        $this->call($endpoint, 'POST', ['json' => $role->toArray()]);

        return $this->getRole($role->name);
    }

    public function updateRole(string $name, KeycloakRole $role): void {
        // PUT /{realm}/clients/{id}/roles/{role-name}
        $endpoint = "{$this->getClientUrl()}/roles/{$name}";
        $this->call($endpoint, 'PUT', [
            'json' => $role->toArray(),
        ]);
    }

    public function deleteRole(string $name): void {
        // PUT /{realm}/clients/{id}/roles/{role-name}
        $endpoint = "{$this->getClientUrl()}/roles/{$name}";
        $this->call($endpoint, 'DELETE');
    }
    //</editor-fold>

    // <editor-fold desc="Users">
    // =========================================================================
    public function createUser(string $email, Role $role): KeycloakUser {
        // POST /{realm}/users
        $endpoint = 'users';

        // Get Group path
        $group = $this->getGroup($role);

        if (!$group) {
            throw new RealmGroupUnknown();
        }

        $input = new KeycloakUser([
            'email'         => $email,
            'groups'        => [$group->path],
            'enabled'       => true,
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

        return $this->getUserByEmail($email);
    }

    public function getUserById(string $id): KeycloakUser {
        // GET /{realm}/users/{id}

        try {
            $result = $this->call("users/{$id}");
        } catch (RequestFailed $exception) {
            if ($exception->isHttpError(Response::HTTP_NOT_FOUND)) {
                throw new RealmUserNotFound($id, $exception);
            }

            throw $exception;
        }

        return new KeycloakUser($result);
    }

    public function updateUser(string $id, KeycloakUser $user): bool {
        // PUT /{realm}/users/{id}
        $endpoint = "users/{$id}";

        $this->call($endpoint, 'PUT', ['json' => $user->toArray()]);

        return true;
    }

    public function getUserByEmail(string $email): ?KeycloakUser {
        // GET /{realm}/users?email={email}
        $endpoint = "users?email={$email}";
        $users    = $this->call($endpoint);

        return is_array($users) && isset($users[0])
            ? new KeycloakUser($users[0])
            : null;
    }

    public function requestResetPassword(string $id): void {
        // PUT /{realm}/users/{id}/execute-actions-email
        $endpoint = "users/{$id}/execute-actions-email";
        $this->call($endpoint, 'PUT', ['json' => ['UPDATE_PASSWORD']]);
    }

    /**
     * @return array<KeycloakGroup>
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
            return new KeycloakGroup($group);
        }, $groups);
    }

    public function addUserToGroup(KeycloakUser|string $user, string $groupId): bool {
        // PUT /{realm}/users/{id}/groups/{groupId}
        $userId   = $user instanceof KeycloakUser ? $user->id : $user;
        $endpoint = "users/{$userId}/groups/{$groupId}";

        $this->call($endpoint, 'PUT');

        return true;
    }

    public function resetPassword(string $id, string $password): bool {
        // PUT /{realm}/users/{id}/reset-password
        $endpoint = "users/{$id}/reset-password";
        // Create new credentials
        $credential = new KeycloakCredential([
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
     * @return array<KeycloakUser>
     */
    public function getUsers(int $limit, int $offset): array {
        $keycloak = rtrim($this->config->get('ep.keycloak.url'), '/');
        $realm    = $this->config->get('ep.keycloak.realm');
        $baseUrl  = "{$keycloak}/auth/realms/{$realm}/custom";
        $params   = http_build_query(['offset' => $offset, 'limit' => $limit]);
        $endpoint = "users?{$params}";

        $result = $this->call($endpoint, 'GET', [], $baseUrl);
        $result = array_map(static function ($item) {
            return new KeycloakUser($item);
        }, $result);

        return $result;
    }

    public function usersCount(): int {
        // GET /{realm}/users/count
        $endpoint = 'users/count';
        $result   = $this->call($endpoint);

        return $result;
    }

    /**
     * @return ObjectIterator<KeycloakUser>
     */
    public function getUsersIterator(): ObjectIterator {
        return new OffsetBasedObjectIterator(
            function (array $variables): array {
                return $this->getUsers($variables['limit'], $variables['offset']);
            },
        );
    }

    public function removeUserFromGroup(KeycloakUser|string $user, string $groupId): bool {
        // DELETE /{realm}/users/{id}/groups/{groupId}
        $userId   = $user instanceof KeycloakUser ? $user->id : $user;
        $endpoint = "users/{$userId}/groups/{$groupId}";

        $this->call($endpoint, 'DELETE');

        return true;
    }
    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    public function getBaseUrl(): string {
        $keycloak = rtrim((string) $this->config->get('ep.keycloak.url'), '/');
        $realm    = $this->config->get('ep.keycloak.realm');

        return "{$keycloak}/auth/admin/realms/{$realm}";
    }

    protected function isEnabled(): bool {
        return $this->config->get('ep.keycloak.enabled')
           && $this->config->get('ep.keycloak.url')
           && $this->config->get('ep.keycloak.client_id')
           && $this->config->get('ep.keycloak.client_secret');
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function call(string $endpoint, string $method = 'GET', array $data = [], string $baseUrl = null): mixed {
        // Enabled?
        if (!$this->isEnabled()) {
            throw new KeycloakDisabled();
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
            ->connectTimeout($timeout)
            ->timeout($timeout)
            ->withHeaders($headers)
            ->asJson();

        // Call
        $json = null;

        try {
            $response = $request->send($method, $endpoint, $data);

            assert($response instanceof IlluminateResponse);

            $json = $response->throw()->json();
        } catch (ConnectionException $exception) {
            throw new KeycloakUnavailable($exception);
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

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<KeycloakRole|Permission> $roles
     *
     * @return Collection<int, KeycloakRole>
     */
    protected function toRoles(array $roles): Collection {
        return (new Collection($roles))
            ->map(static function (KeycloakRole|Permission $role): KeycloakRole {
                if ($role instanceof Permission) {
                    $role = new KeycloakRole([
                        'id'   => $role->getKey(),
                        'name' => $role->key,
                    ]);
                }

                return $role;
            });
    }
    //</editor-fold>
}
