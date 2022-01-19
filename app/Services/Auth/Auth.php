<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Auth\Contracts\Rootable;
use App\Services\Auth\Permissions\Administer;
use App\Services\Auth\Permissions\AssetsDownload;
use App\Services\Auth\Permissions\AssetsSupport;
use App\Services\Auth\Permissions\AssetsSync;
use App\Services\Auth\Permissions\AssetsView;
use App\Services\Auth\Permissions\ContractsDownload;
use App\Services\Auth\Permissions\ContractsSupport;
use App\Services\Auth\Permissions\ContractsSync;
use App\Services\Auth\Permissions\ContractsView;
use App\Services\Auth\Permissions\CustomersDownload;
use App\Services\Auth\Permissions\CustomersSupport;
use App\Services\Auth\Permissions\CustomersSync;
use App\Services\Auth\Permissions\CustomersView;
use App\Services\Auth\Permissions\OrgAdminister;
use App\Services\Auth\Permissions\QuotesDownload;
use App\Services\Auth\Permissions\QuotesSupport;
use App\Services\Auth\Permissions\QuotesSync;
use App\Services\Auth\Permissions\QuotesView;
use App\Services\Auth\Permissions\RequestsAssetAdd;
use App\Services\Auth\Permissions\RequestsAssetChange;
use App\Services\Auth\Permissions\RequestsContractChange;
use App\Services\Auth\Permissions\RequestsCustomerChange;
use App\Services\Auth\Permissions\RequestsQuoteAdd;
use App\Services\Auth\Permissions\RequestsQuoteChange;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;

use function in_array;
use function is_null;

class Auth {
    public function __construct(
        protected Factory $auth,
        protected CurrentOrganization $organization,
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

        return $this->organization->defined()
            ? $user->isEnabled($this->organization->get())
            : $user->isEnabled(null);
    }

    /**
     * @return array<\App\Services\Auth\Permission>
     */
    public function getPermissions(): array {
        return [
            // Assets
            new AssetsView(),
            new AssetsSupport(),
            new AssetsDownload(),
            new AssetsSync(),
            // Contracts
            new ContractsView(),
            new ContractsSupport(),
            new ContractsDownload(),
            new ContractsSync(),
            // Customers
            new CustomersView(),
            new CustomersSupport(),
            new CustomersDownload(),
            new CustomersSync(),
            // Quotes
            new QuotesView(),
            new QuotesSupport(),
            new QuotesDownload(),
            new QuotesSync(),
            // "+ Request" buttons
            new RequestsAssetAdd(),
            new RequestsAssetChange(),
            new RequestsQuoteAdd(),
            new RequestsQuoteChange(),
            new RequestsCustomerChange(),
            new RequestsContractChange(),
            // Your Organization
            new OrgAdminister(),
            // Portal Administration
            new Administer(),
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
