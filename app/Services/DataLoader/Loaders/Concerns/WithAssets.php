<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Customer;
use App\Models\Model;
use App\Models\Organization;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\OrganizationFactory;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;
use Traversable;

trait WithAssets {
    protected OrganizationFactory $resellers;
    protected CustomerFactory     $customers;
    protected LocationFactory     $locations;
    protected ContactFactory      $contacts;
    protected AssetFactory        $assets;
    protected bool                $withAssets = false;

    public function isWithAssets(): bool {
        return $this->withAssets;
    }

    public function setWithAssets(bool $withAssets): static {
        $this->withAssets = $withAssets;

        return $this;
    }

    protected function loadAssets(Model $owner): bool {
        // Update assets
        $factory   = $this->getAssetsFactory();
        $updated   = [];
        $customers = [];
        $resellers = [];

        foreach ($this->getCurrentAssets($owner) as $asset) {
            try {
                $asset = $factory->create($asset);

                if ($asset) {
                    $updated[]                                   = $asset->getKey();
                    $customers[(string) $asset->customer_id]     = true;
                    $resellers[(string) $asset->organization_id] = true;
                }
            } catch (Throwable $exception) {
                Log::warning(__METHOD__, [
                    'asset'     => $asset,
                    'exception' => $exception,
                ]);
            }
        }

        // Update outdated
        $iterator = $this->getOutdatedAssets($owner, $updated)?->iterator()->safe() ?? [];

        unset($updated);

        foreach ($iterator as $outdated) {
            $asset = $this->client->getAssetById($outdated->getKey());

            if ($asset) {
                try {
                    $asset = $factory->create($asset);

                    if ($asset) {
                        $customers[(string) $asset->customer_id]     = true;
                        $resellers[(string) $asset->organization_id] = true;
                    }
                } catch (Throwable $exception) {
                    Log::warning(__METHOD__, [
                        'asset'     => $asset,
                        'exception' => $exception,
                    ]);
                }
            } else {
                $asset->delete();
            }
        }

        // Update Customers
        foreach ($customers as $id => $_) {
            $customer = $this->customers->find(Company::create([
                'id'           => $id,
                'companyTypes' => [['type' => 'CUSTOMER']],
            ]));

            if ($customer) {
                $this->updateCustomerCountable($customer);
            }
        }

        unset($customers);

        // Update Resellers
        foreach ($resellers as $id => $_) {
            $reseller = $this->resellers->find(Company::create([
                'id'           => $id,
                'companyTypes' => [['type' => 'RESELLER']],
            ]));

            if ($reseller) {
                $this->updateResellerCountable($reseller);
            }
        }

        unset($resellers);

        // Return
        return true;
    }

    /**
     * @return \Traversable<\App\Services\DataLoader\Schema\Asset>
     */
    abstract protected function getCurrentAssets(Model $owner): Traversable;

    /**
     * @param array<string> $current
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Asset>|null
     */
    abstract protected function getOutdatedAssets(Model $owner, array $current): ?Builder;

    protected function updateCustomerCountable(Customer $customer): void {
        $customer->locations_count = $customer->locations()->count();
        $customer->contacts_count  = $customer->contacts()->count();
        $customer->assets_count    = $customer->assets()->count();
        $customer->save();
    }

    protected function updateResellerCountable(Organization $reseller): void {
        $reseller->locations_count = $reseller->locations()->count();
        $reseller->assets_count    = $reseller->assets()->count();
        $reseller->save();
    }

    protected function getAssetsFactory(): AssetFactory {
        $customers = (clone $this->customers)
            ->setLocationFactory($this->locations)
            ->setContactsFactory($this->contacts);
        $resellers = (clone $this->resellers)
            ->setLocationFactory($this->locations);
        $factory   = (clone $this->assets)
            ->setOrganizationFactory($resellers)
            ->setCustomersFactory($customers);

        return $factory;
    }
}
