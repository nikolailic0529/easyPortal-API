<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Models\Organization;
use App\Models\Role as RoleModel;
use App\Services\KeyCloak\Client\Exceptions\EndpointException;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakClient;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakGroup;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\Role;
use App\Services\KeyCloak\Client\Types\User;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\Factory;

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

    public function getOrganizationGroup(Organization $organization): Group {
        // GET /{realm}/groups/{id}
        if (!$organization->keycloak_group_id) {
            throw new InvalidKeyCloakGroup();
        }

        $endpoint = "groups/{$organization->keycloak_group_id}";
        $result   = $this->call($endpoint);
        $result   = new Group($result);

        return $result;
    }

    /**
     * @return array<\App\Services\KeyCloak\Client\Types\Role>
     */
    public function getRoles(): array {
        // GET /{realm}/clients/{id}/roles
        $clientId = (string) $this->config->get('ep.keycloak.client_uuid');
        if (!$clientId) {
            throw new InvalidKeyCloakClient();
        }

        $endpoint = "clients/{$clientId}/roles";
        $result   = $this->call($endpoint);
        $result   = array_map(static function ($item) {
            return new Role($item);
        }, $result);

        return $result;
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

        if (!empty($roles)) {
            $this->addRolesToGroup($group, $roles);
        }

        return $group;
    }

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $roles
     */
    public function editSubGroup(RoleModel $role, string $name, array $roles = []): void {
        // PUT /{realm}/groups/{id}
        $endpoint = "groups/{$role->id}";
        $input    = new Group(['name' => $name]);
        $this->call($endpoint, 'PUT', ['json' => $input->toArray()]);

        // Sync Roles
    }

    public function deleteGroup(RoleModel $role): void {
        // DELETE /{realm}/groups/{id}
        $endpoint = "groups/{$role->getKey()}";
        $this->call($endpoint, 'DELETE');
    }

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\Role> $roles
     */
    protected function addRolesToGroup(Group $group, array $roles = []): Group {
        // GET {realm}/groups/{groupId}/role-mappings/clients/{clientId}/
        $clientId = $this->config->get('ep.keycloak.client_uuid');
        $endpoint = "groups/{$group->id}/role-mappings/clients/{$clientId}";
        $result   = $this->call($endpoint, 'POST', ['json' => $roles]);
        $result   = new Group($result);

        return $result;
    }

    /**
     * @return array<\App\Services\KeyCloak\Client\Types\Role>
     */
    public function roles(): array {
        // GET /{realm}/clients/{id}/roles
        $clientId = (string) $this->config->get('ep.keycloak.client_uuid');
        $endpoint = "clients/{$clientId}/roles";
        $result   = $this->call($endpoint);
        $result   = array_map(static function ($item) {
            return new Role($item);
        }, $result);

        return $result;
    }
    // </editor-fold>

    // <editor-fold desc="API">
    // =========================================================================
    public function getBaseUrl(): string {
        $keycloak = rtrim($this->config->get('ep.keycloak.url'), '/');
        $realm    = $this->config->get('ep.keycloak.realm');

        return " {$keycloak}/auth/admin/realms/{$realm}";
    }

    protected function isEnabled(): bool {
        return $this->config->get('ep.keycloak.url')
            && $this->config->get('ep.keycloak.client_id')
            && $this->config->get('ep.keycloak.client_secret');
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function call(string $endpoint, string $method = 'GET', array $options = []): mixed {
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
            $error = new EndpointException($endpoint, $exception);
            $this->handler->report($error);
            throw $error;
        }

        return $response->json();
    }

    // </editor-fold>
}
