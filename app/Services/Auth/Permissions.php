<?php declare(strict_types = 1);

namespace App\Services\Auth;

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
use App\Services\Auth\Permissions\MapWidgetAssets;
use App\Services\Auth\Permissions\MapWidgetAssetSplit;
use App\Services\Auth\Permissions\MapWidgetContractValue;
use App\Services\Auth\Permissions\MapWidgetCoverage;
use App\Services\Auth\Permissions\MapWidgetCustomers;
use App\Services\Auth\Permissions\MapWidgetInsights;
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

use function array_merge;

class Permissions {
    /**
     * @var array<\App\Services\Auth\Permission>
     */
    protected array $permissions;

    public function __construct() {
        // empty
    }

    /**
     * @return array<\App\Services\Auth\Permission>
     */
    public function get(): array {
        if (!isset($this->permissions)) {
            $this->permissions = $this->default();
        }

        return $this->permissions;
    }

    /**
     * @param array<\App\Services\Auth\Permission> $permissions
     */
    public function set(array $permissions): static {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @param array<\App\Services\Auth\Permission> $permissions
     */
    public function add(array $permissions): static {
        $this->permissions = array_merge($this->get(), $permissions);

        return $this;
    }

    /**
     * @return array<\App\Services\Auth\Permission>
     */
    protected static function default(): array {
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
            // Map
            new MapWidgetAssets(),
            new MapWidgetAssetSplit(),
            new MapWidgetContractValue(),
            new MapWidgetCoverage(),
            new MapWidgetCustomers(),
            new MapWidgetInsights(),
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
}
