<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Role;
use App\Services\Keycloak\Client\Client;

class Delete {
    public function __construct(
        protected Client $client,
    ) {
        // empty
    }

    /**
     * @return  array<string, mixed>
     */
    public function __invoke(Role $role): array {
        // Users?
        if ($role->users()->exists()) {
            throw new DeleteImpossibleAssignedToUsers($role);
        }

        // Delete
        $result = $this->client->deleteGroup($role)
            && $role->delete();

        // Return
        return [
            'result' => $result,
        ];
    }
}
