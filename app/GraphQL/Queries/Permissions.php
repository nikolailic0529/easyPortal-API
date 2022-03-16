<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Concerns\AvailablePermissions;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Collection;

class Permissions {
    use AvailablePermissions;

    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @return Collection<int,Permission>
     */
    public function __invoke(): Collection {
        $organization = $this->organization->get();
        $available    = $this->getAvailablePermissions($organization);
        $permissions  = Permission::query()
            ->whereIn('key', $available)
            ->get();

        return $permissions;
    }

    protected function getAuth(): Auth {
        return $this->auth;
    }
}
