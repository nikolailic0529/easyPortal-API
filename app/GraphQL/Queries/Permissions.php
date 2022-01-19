<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Collection;

class Permissions {
    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @return \Illuminate\Support\Collection<\App\Models\Permission>
     */
    public function __invoke(): Collection {
        $organization = $this->organization->get();
        $available    = (new Collection($this->auth->getAvailablePermissions($organization)))
            ->map(static function (AuthPermission $permission): string {
                return $permission->getName();
            });
        $permissions  = Permission::query()
            ->whereIn('key', $available)
            ->get();

        return $permissions;
    }
}
