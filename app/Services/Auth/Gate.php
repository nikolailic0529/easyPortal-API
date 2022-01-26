<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Services\Auth\Concerns\AvailablePermissions;
use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

use function in_array;

class Gate {
    use AvailablePermissions;

    public function __construct(
        protected CurrentOrganization $currentOrganization,
        protected Auth $auth,
    ) {
        // empty
    }

    protected function getAuth(): Auth {
        return $this->auth;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function before(Authenticatable|null $user, string $ability, array $arguments): ?bool {
        // Enabled?
        if (!$this->getAuth()->isEnabled($user)) {
            return false;
        }

        // Root?
        if ($this->getAuth()->isRoot($user)) {
            return true;
        }

        // Permission?
        if ($this->isPermission($ability) && !$this->hasPermission($user, $ability)) {
            return false;
        }

        // Check gates/policies
        return null;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function after(Authenticatable|null $user, string $ability, bool|null $result, array $arguments): ?bool {
        return $result === true || ($result === null && $this->isPermission($ability));
    }

    protected function isPermission(string $permission): bool {
        return (new Collection($this->getAuth()->getPermissions()))
            ->contains(static function (Permission $p) use ($permission): bool {
                return $p->getName() === $permission;
            });
    }

    protected function hasPermission(Authenticatable|null $user, string $permission): bool {
        $permissions = $user instanceof HasPermissions ? $user->getPermissions() : [];
        $has         = $permissions && in_array($permission, $permissions, true);

        return $has;
    }
}
