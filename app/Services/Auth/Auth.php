<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;

use function in_array;
use function is_null;

class Auth {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function isRoot(Authenticatable|null $user): bool {
        return $user instanceof Rootable
            && $user->isRootable()
            && in_array($user->getAuthIdentifier(), (array) $this->config->get('ep.root_users'), true);
    }

    /**
     * @return array<string,string>
     */
    public function getPermissions(): array {
        return [
            'view-assets',
            'view-contracts',
            'view-quotes',
            'view-customers',
            'edit-organization',
        ];
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
