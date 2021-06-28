<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

use function array_key_exists;

class RolePermissions {
    public function __construct(
        protected Client $client,
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(Role $role, array $args): Collection {
        $group        = $this->client->getGroup($role);
        $clientId     = (string) $this->config->get('ep.keycloak.client_id');
        $clientRoles  = $group->clientRoles;
        $currentRoles = [];
        if (array_key_exists($clientId, $clientRoles)) {
            $currentRoles = $clientRoles[$clientId];
        }
        $permissions = Permission::whereIn('key', $currentRoles)->pluck('id');
        return $permissions;
    }
}
