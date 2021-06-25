<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

use function in_array;
use function is_null;

class Auth {
    public function __construct() {
        // empty
    }

    public function isRoot(Authenticatable|null $user): bool {
        return $user instanceof Rootable && $user->isRoot();
    }

    /**
     * @return array<string,string>
     */
    public function getPermissions(): array {
        return [
            // Assets
            'assets-view',
            'assets-support',
            'assets-download',
            // Contracts
            'contracts-view',
            'contracts-support',
            'contracts-download',
            // Customers
            'customers-view',
            'customers-support',
            'customers-download',
            // Quotes
            'quotes-view',
            'quotes-support',
            'quotes-download',
            // "+ Request" buttons
            'requests-asset-add',
            'requests-asset-change',
            'requests-quote-add',
            'requests-quote-change',
            'requests-customer-change',
            'requests-contract-change',
            // Your Organization
            'org-administer',
            // Portal Administration
            'administer',
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
