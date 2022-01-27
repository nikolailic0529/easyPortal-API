<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\Rootable;
use App\Services\Auth\Permissions\Markers\IsRoot;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;

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
}
