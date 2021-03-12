<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Customer;
use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\OrganizationFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Loaders\Concerns\WithContacts;
use App\Services\DataLoader\Loaders\Concerns\WithLocations;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;
use Traversable;

class CustomerLoader extends Loader {
    use WithLocations;
    use WithContacts;
    use WithAssets;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected OrganizationFactory $resellers,
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
        // Load company
        $company = $this->client->getCompanyById($id);

        if (!$company) {
            $customer = Customer::query()->whereKey($id)->first();

            if ($customer) {
                $customer->delete();
            }

            return false;
        }

        // Load customer
        $customers = $this->getCustomersFactory();
        $customer  = $customers->create($company);

        if ($this->isWithAssets()) {
            $this->loadAssets($customer);
        }

        $this->updateCustomerCountable($customer);

        // Return
        return true;
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getCurrentAssets(Model $owner): Traversable {
        return $this->client->getAssetsByCustomerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getOutdatedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Customer
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    //</editor-fold>

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
    // </editor-fold>
}
