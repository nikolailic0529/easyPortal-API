<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use WeakMap;

use function in_array;

class Gate {
    /**
     * @var \WeakMap<\App\Models\User,array<string,array{result:?bool}>>
     */
    private WeakMap $cache;

    /**
     * @var array<string, bool>
     */
    private array $permissions = [];

    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
    ) {
        $this->cache = new WeakMap();
    }

    protected function getAuth(): Auth {
        return $this->auth;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function before(Authenticatable|null $user, string $ability, array $arguments): ?bool {
        // Cached?
        if ($user && isset($this->cache[$user][$ability])) {
            return $this->cache[$user][$ability]['result'];
        }

        // Check
        $can = $this->can($user, $ability);

        if ($user) {
            if (!isset($this->cache[$user])) {
                $this->cache[$user] = [];
            }

            $this->cache[$user][$ability] = ['result' => $can];
        }

        // Return
        return $can;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function after(Authenticatable|null $user, string $ability, bool|null $result, array $arguments): ?bool {
        return $result === true || ($result === null && $this->isPermission($ability));
    }

    protected function can(Authenticatable|null $user, string $ability): ?bool {
        // Enabled?
        $org     = $this->organization->defined() ? $this->organization->get() : null;
        $enabled = $this->getAuth()->isEnabled($user, $org);

        if (!$enabled) {
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

    protected function isPermission(string $permission): bool {
        if (!isset($this->permissions[$permission])) {
            $this->permissions[$permission] = (new Collection($this->getAuth()->getPermissions()))
                ->contains(static function (Permission $p) use ($permission): bool {
                    return $p->getName() === $permission;
                });
        }

        return $this->permissions[$permission];
    }

    protected function hasPermission(Authenticatable|null $user, string $permission): bool {
        $permissions = $user instanceof HasPermissions ? $user->getPermissions() : [];
        $has         = $permissions && in_array($permission, $permissions, true);

        return $has;
    }
}
