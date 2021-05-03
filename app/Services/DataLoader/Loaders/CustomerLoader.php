<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Customer;
use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Loaders\Concerns\WithContacts;
use App\Services\DataLoader\Loaders\Concerns\WithLocations;
use App\Services\DataLoader\Schema\Company;
use App\Services\Tenant\Eloquent\OwnedByTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class CustomerLoader extends Loader {
    use GlobalScopes;
    use WithLocations;
    use WithContacts;
    use WithAssets;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected ResellerFactory $resellers,
        protected CustomerFactory $customers,
        protected LocationFactory $locations,
        protected ContactFactory $contacts,
        protected AssetFactory $assets,
        protected DocumentFactory $documents,
    ) {
        parent::__construct($logger, $client);
    }

    // <editor-fold desc="API">
    // =========================================================================
    public function load(string $id): bool {
        // Load company
        $company = $this->client->getCompanyById($id);

        $this->callWithoutGlobalScopes([OwnedByTenantScope::class], function () use ($id, $company): void {
            $this->process($id, $company);
        });

        // Return
        return (bool) $company;
    }

    protected function process(string $id, ?Company $company): void {
        $customer = null;

        try {
            if ($company) {
                $customers = $this->getCustomersFactory();
                $customer  = $customers->create($company);

                if ($this->isWithAssets()) {
                    $this->loadAssets($customer);
                }
            } else {
                $customer = Customer::query()->whereKey($id)->first();

                if ($customer) {
                    $this->logger->warning('Customer found in database but not found in Cosmos.', [
                        'id' => $id,
                    ]);
                }
            }
        } finally {
            if ($customer) {
                $this->updateCustomerCountable($customer);
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getCurrentAssets(Model $owner): QueryIterator {
        return $this->isWithAssetsDocuments()
            ? $this->client->getAssetsWithDocumentsByCustomerId($owner->getKey())
            : $this->client->getAssetsByCustomerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getMissedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Customer
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function getCustomersFactory(): CustomerFactory {
        return $this->customers
            ->setLocationFactory(
                $this->isWithLocations() ? $this->locations : null,
            )
            ->setContactsFactory(
                $this->isWithContacts() ? $this->contacts : null,
            );
    }
    // </editor-fold>
}
