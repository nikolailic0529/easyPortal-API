<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Loaders\Concerns\WithContacts;
use App\Services\DataLoader\Loaders\Concerns\WithLocations;
use Psr\Log\LoggerInterface;

use function array_filter;

class CustomerLoader extends Loader {
    use WithLocations;
    use WithContacts;
    use WithAssets;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected CustomerFactory $customers,
        protected LocationFactory $locations,
        protected ContactFactory $contacts,
        protected AssetFactory $assets,
    ) {
        parent::__construct($logger, $client);
    }

    // <editor-fold desc="API">
    // =========================================================================
    public function load(string $id): bool {
        // TODO [DataLoader] Would be good to load only necessary objects
        //      according to current settings.

        // FIXME [DataLoader] We no need to cache Assets here, but it is not
        //      possible to disable cache now.

        // Load company
        $company = $this->client->getCompanyById($id);

        if (!$company) {
            return false;
        }

        // Load customer
        $customers = $this->getCustomersFactory();
        $customer  = $customers->create($company);

        if (!$this->isWithAssets()) {
            return true;
        }

        // Load Assets
        $factory = $this->getCustomerAssetsFactory();
        $assets  = [];

        foreach ($this->client->getAssetsByCustomerId($customer->getKey()) as $asset) {
            $asset    = $factory->create($asset);
            $assets[] = $asset?->getKey();
        }

        // Update countable
        $customer->locations_count = $customer->locations()->count();
        $customer->contacts_count  = $customer->contacts()->count();
        $customer->assets_count    = $customer->assets()->count();
        $customer->save();

        // Some assets can be removed, we need find and update them
        $assets  = array_filter($assets);
        $factory = $this->getAssetsFactory();
        $removed = $customer
            ->assets()
            ->whereNotIn('id', $assets)
            ->iterator()
            ->safe();

        foreach ($removed as $asset) {
            /** @var \App\Models\Asset $asset */
            $loaded = $this->client->getAssetById($asset->getKey());

            if ($loaded) {
                $factory->create($loaded);
            } else {
                $asset->customer = null;
                $asset->location = null;
                $asset->save();
            }
        }

        // Return
        return true;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function getCustomersFactory(): CustomerFactory {
        return (clone $this->customers)
            ->setLocationFactory(
                $this->isWithLocations() ? $this->locations : null,
            )
            ->setContactsFactory(
                $this->isWithContacts() ? $this->contacts : null,
            );
    }

    protected function getCustomerAssetsFactory(): AssetFactory {
        return clone $this->assets;
    }

    protected function getAssetsFactory(): AssetFactory {
        $customers = (clone $this->customers)
            ->setLocationFactory($this->locations)
            ->setContactsFactory($this->contacts);
        $factory   = (clone $this->assets)
            ->setCustomersFactory($customers);

        return $factory;
    }
    // </editor-fold>
}
