<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Auth\Contracts\Rootable;
use App\Services\Auth\Permissions\Markers\IsRoot;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;

use function in_array;
use function is_null;

class Auth {
    public function __construct(
        protected Factory $auth,
        protected RootOrganization $rootOrganization,
        protected CurrentOrganization $currentOrganization,
        protected Permissions $permissions,
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

    public function isEnabled(Authenticatable|null $user): bool {
        if (!($user instanceof Enableable)) {
            return true;
        }

        return $this->currentOrganization->defined()
            ? $user->isEnabled($this->currentOrganization->get())
            : $user->isEnabled(null);
    }

    /**
     * @return array<\App\Services\Auth\Permission>
     */
    public function getPermissions(): array {
        return $this->permissions->get();
    }

    /**
     * @return array<\App\Services\Auth\Permission>
     */
    public function getAvailablePermissions(Organization $organization): array {
        $isRoot      = $this->rootOrganization->is($organization);
        $permissions = [];

        foreach ($this->getPermissions() as $permission) {
            if (!$isRoot && $permission instanceof IsRoot) {
                continue;
            }

            $permissions[] = $permission;
        }

        return $permissions;
    }

    protected function hasPermission(Authenticatable|null $user, string $permission): bool {
        $permissions = $user instanceof HasPermissions ? $user->getPermissions() : [];
        $has         = $permissions && in_array($permission, $permissions, true);

        return $has;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function gateBefore(Authenticatable|null $user, string $ability, array $arguments): ?bool {
        // Enabled?
        if (!$this->isEnabled($user)) {
            return false;
        }

        // Root?
        if ($this->isRoot($user)) {
            return true;
        }

        // Permission?
        if (!$this->hasPermission($user, $ability)) {
            return false;
        }

        // Check gates/policies
        return null;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function gateAfter(Authenticatable|null $user, string $ability, bool|null $result, array $arguments): ?bool {
        return is_null($result);
    }
}
