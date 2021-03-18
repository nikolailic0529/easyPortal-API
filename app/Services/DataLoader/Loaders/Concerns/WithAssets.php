<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Customer;
use App\Models\Model;
use App\Models\Reseller;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * @mixin \App\Services\DataLoader\Loader
 */
trait WithAssets {
    protected ResellerFactory $resellers;
    protected CustomerFactory $customers;
    protected LocationFactory $locations;
    protected ContactFactory  $contacts;
    protected AssetFactory    $assets;
    protected bool            $withAssets = false;

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
        $prefetch  = function (array $assets): void {
            $this->assets->prefetch($assets);
        };

        foreach ($this->getCurrentAssets($owner)->each($prefetch) as $asset) {
            try {
                $asset = $factory->create($asset);

                if ($asset) {
                    $updated[]                               = $asset->getKey();
                    $customers[(string) $asset->customer_id] = true;
                    $resellers[(string) $asset->reseller_id] = true;
                }
            } catch (Throwable $exception) {
                $this->logger->warning(__METHOD__, [
                    'asset'     => $asset,
                    'exception' => $exception,
                ]);
            }
        }

        // Update missed
        $iterator = $this->getMissedAssets($owner, $updated)?->iterator()->safe() ?? [];

        unset($updated);

        foreach ($iterator as $missed) {
            /** @var \App\Models\Asset $missed */
            $asset = $this->client->getAssetById($missed->getKey());

            if ($asset) {
                try {
                    $asset = $factory->create($asset);

                    if ($asset) {
                        $customers[(string) $asset->customer_id] = true;
                        $resellers[(string) $asset->reseller_id] = true;
                    }
                } catch (Throwable $exception) {
                    $this->logger->warning(__METHOD__, [
                        'asset'     => $asset,
                        'exception' => $exception,
                    ]);
                }
            } else {
                $missed->customer = null;
                $missed->reseller = null;
                $missed->save();

                $this->logger->error('Asset found in database but not found in Cosmos.', [
                    'id' => $missed->getKey(),
                ]);
            }
        }

        // Update Customers
        foreach ($customers as $id => $_) {
            if (!$id) {
                continue;
            }

            $customer = $this->customers->find(Company::create([
                'id' => $id,
            ]));

            if ($customer) {
                $this->updateCustomerCountable($customer);
            }
        }

        unset($customers);

        // Update Resellers
        foreach ($resellers as $id => $_) {
            if (!$id) {
                continue;
            }

            $reseller = $this->resellers->find(Company::create([
                'id' => $id,
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
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\Asset>
     */
    abstract protected function getCurrentAssets(Model $owner): QueryIterator;

    /**
     * @param array<string> $current
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Asset>|null
     */
    abstract protected function getMissedAssets(Model $owner, array $current): ?Builder;

    protected function updateCustomerCountable(Customer $customer): void {
        $customer->locations_count = $customer->locations()->count();
        $customer->contacts_count  = $customer->contacts()->count();
        $customer->assets_count    = $customer->assets()->count();
        $customer->save();
    }

    protected function updateResellerCountable(Reseller $reseller): void {
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
            ->setResellerFactory($resellers)
            ->setCustomersFactory($customers);

        return $factory;
    }
}
