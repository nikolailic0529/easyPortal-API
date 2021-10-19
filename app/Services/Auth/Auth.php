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
     * @return array<\App\Services\Auth\Permission>
     */
    public function getPermissions(): array {
        return [
            // Assets
            new Permission('assets-view', orgAdmin: true),
            new Permission('assets-support', orgAdmin: true),
            new Permission('assets-download', orgAdmin: true),
            new Permission('assets-sync', orgAdmin: true),
            // Contracts
            new Permission('contracts-view', orgAdmin: true),
            new Permission('contracts-support', orgAdmin: true),
            new Permission('contracts-download', orgAdmin: true),
            new Permission('contracts-sync', orgAdmin: true),
            // Customers
            new Permission('customers-view', orgAdmin: true),
            new Permission('customers-support', orgAdmin: true),
            new Permission('customers-download', orgAdmin: true),
            new Permission('customers-sync', orgAdmin: true),
            // Quotes
            new Permission('quotes-view', orgAdmin: true),
            new Permission('quotes-support', orgAdmin: true),
            new Permission('quotes-download', orgAdmin: true),
            new Permission('quotes-sync', orgAdmin: true),
            // "+ Request" buttons
            new Permission('requests-asset-add', orgAdmin: true),
            new Permission('requests-asset-change', orgAdmin: true),
            new Permission('requests-quote-add', orgAdmin: true),
            new Permission('requests-quote-change', orgAdmin: true),
            new Permission('requests-customer-change', orgAdmin: true),
            new Permission('requests-contract-change', orgAdmin: true),
            // Your Organization
            new Permission('org-administer', orgAdmin: true),
            // Portal Administration
            new Permission('administer', orgAdmin: false),
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
