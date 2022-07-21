<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\Permissions\Composite;
use App\Services\Auth\Contracts\Permissions\IsRoot;
use App\Services\Auth\Contracts\Rootable;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Collection;

use function array_intersect;
use function array_pop;
use function array_values;

class Auth {
    public function __construct(
        protected Factory $auth,
        protected Permissions $permissions,
        protected RootOrganization $rootOrganization,
    ) {
        // empty
    }

    public function getUser(): ?User {
        $user = $this->auth->guard()->user();

        if (!($user instanceof User)) {
            $user = null;
        }

        return $user;
    }

    public function isRoot(Authenticatable|null $user): bool {
        return $user instanceof Rootable && $user->isRoot();
    }

    public function isEnabled(Authenticatable|null $user, ?Organization $organization): bool {
        if (!($user instanceof Enableable)) {
            return true;
        }

        return $user->isEnabled($organization);
    }

    /**
     * @return array<Permission>
     */
    public function getPermissions(): array {
        return $this->permissions->get();
    }

    /**
     * @return array<string>
     */
    public function getAvailablePermissions(?Organization $organization): array {
        $isRoot      = $organization && $this->rootOrganization->is($organization);
        $permissions = [];

        foreach ($this->getPermissions() as $permission) {
            if (!$isRoot && $permission instanceof IsRoot) {
                continue;
            }

            $permissions[] = $permission->getName();
        }

        return $permissions;
    }

    /**
     * @return array<string>
     */
    public function getOrganizationUserPermissions(Organization $organization, User $user): array {
        $permissions = $user->getOrganizationPermissions($organization);
        $permissions = $this->getActualPermissions($organization, $permissions);

        return $permissions;
    }

    /**
     * @param array<string> $permissions
     *
     * @return array<string>
     */
    public function getActualPermissions(?Organization $organization, array $permissions): array {
        $valid       = $this->getAvailablePermissions($organization);
        $permissions = $this->expand($permissions);
        $permissions = array_intersect($permissions, $valid);
        $permissions = array_values($permissions);

        return $permissions;
    }

    /**
     * @param array<string> $permissions
     *
     * @return array<string>
     */
    private function expand(array $permissions): array {
        $stack    = (new Collection($this->getPermissions()))
            ->keyBy(static function (Permission $permission): string {
                return $permission->getName();
            })
            ->only($permissions)
            ->all();
        $expanded = [];

        while ($stack) {
            // Added?
            $permission = array_pop($stack);

            if (isset($expanded[$permission->getName()])) {
                continue;
            }

            // Add
            $expanded[$permission->getName()] = $permission->getName();

            // Composite?
            if ($permission instanceof Composite) {
                foreach ($permission->getPermissions() as $inherited) {
                    $stack[] = $inherited;
                }
            }
        }

        return array_values($expanded);
    }
}
