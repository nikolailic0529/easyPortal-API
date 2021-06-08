<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Customer;
use App\Models\Model;
use App\Models\Reseller;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\DataLoader\Exceptions\InvalidData;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Schema\Company;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

use function array_filter;

/**
 * @mixin \App\Services\DataLoader\Loader
 */
trait WithAssets {
    protected Dispatcher      $dispatcher;
    protected ResellerFactory $resellers;
    protected CustomerFactory $customers;
    protected LocationFactory $locations;
    protected ContactFactory  $contacts;
    protected AssetFactory    $assets;
    protected DocumentFactory $documents;
    protected bool            $withAssets          = false;
    protected bool            $withAssetsDocuments = false;

    public function isWithAssets(): bool {
        return $this->withAssets;
    }

    public function setWithAssets(bool $withAssets): static {
        $this->withAssets = $withAssets;

        return $this;
    }

    public function isWithAssetsDocuments(): bool {
        return $this->isWithAssets() && $this->withAssetsDocuments;
    }

    public function setWithAssetsDocuments(bool $withAssetsDocuments): static {
        $this->withAssetsDocuments = $withAssetsDocuments;

        return $this;
    }

    protected function loadAssets(Model $owner): bool {
        // Update assets
        $factory   = $this->getAssetsFactory();
        $updated   = [];
        $resellers = [];
        $prefetch  = function (array $assets) use ($factory): void {
            $factory->prefetch($assets, true, function (Collection $assets): void {
                if ($this->isWithAssetsDocuments()) {
                    $assets->loadMissing('warranties');
                    $assets->loadMissing('warranties.services');
                }
            });
            $factory->getCustomerFactory()?->prefetch($assets, false, static function (Collection $customers): void {
                $customers->loadMissing('locations');
            });
            $this->getResellersFactory()?->prefetch($assets, false);
            $factory->getDocumentFactory()?->prefetch($assets, false, static function (Collection $documents): void {
                $documents->loadMissing('entries');
                $documents->loadMissing('entries.product');
            });
        };

        foreach ($this->getCurrentAssets($owner)->each($prefetch) as $asset) {
            try {
                $model = $factory->create($asset);

                if ($model) {
                    $resellerId                          = (string) $model->reseller_id;
                    $customerId                          = (string) $model->customer_id;
                    $updated[]                           = $model->getKey();
                    $resellers[$resellerId][$customerId] = $customerId;
                }
            } catch (InvalidData $exception) {
                $this->dispatcher->dispatch(new ObjectSkipped($asset, $exception));
            } catch (Throwable $exception) {
                $this->logger->warning('Failed to process Asset.', [
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
                    $model = $factory->create($asset);

                    if ($model) {
                        $resellerId                          = (string) $model->reseller_id;
                        $customerId                          = (string) $model->customer_id;
                        $resellers[$resellerId][$customerId] = $customerId;
                    }
                } catch (InvalidData $exception) {
                    $this->dispatcher->dispatch(new ObjectSkipped($asset, $exception));
                } catch (Throwable $exception) {
                    $this->logger->warning('Failed to process Asset.', [
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

        // Update Resellers/Customers
        foreach ($resellers as $resellerId => $customers) {
            // Get Reseller
            $reseller = null;

            if ($resellerId) {
                $reseller = $this->getResellersFactory()->find(new Company([
                    'id' => $resellerId,
                ]));
            }

            // Update Customers
            $customers = array_filter($customers);

            foreach ($customers as $customerId) {
                $customer = $this->customers->find(new Company([
                    'id' => $customerId,
                ]));

                if ($customer) {
                    $this->updateCustomerCountable($customer);
                }
            }

            // Add new customers to Reseller
            if ($reseller) {
                $reseller->customers()->syncWithoutDetaching($customers);

                unset($reseller->customers);
            }

            // Update Reseller
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
        $reseller->customers_count = $reseller->customers()->count();
        $reseller->assets_count    = $reseller->assets()->count();
        $reseller->save();
    }

    protected function getAssetsFactory(): AssetFactory {
        $this
            ->getResellersFactory()
            ->setLocationFactory($this->locations);

        $customers = $this->customers
            ->setLocationFactory($this->locations)
            ->setContactsFactory($this->contacts);
        $documents = $this->isWithAssetsDocuments()
            ? $this->documents->setContactsFactory($this->contacts)
            : null;
        $factory   = $this->assets
            ->setCustomersFactory($customers)
            ->setDocumentFactory($documents)
            ->setContactsFactory($this->contacts);

        return $factory;
    }

    protected function getResellersFactory(): ResellerFactory {
        return $this->resellers;
    }
}
