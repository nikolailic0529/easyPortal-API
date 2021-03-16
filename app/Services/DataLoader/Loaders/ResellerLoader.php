<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Model;
use App\Models\Organization;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\OrganizationFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Loaders\Concerns\WithLocations;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class ResellerLoader extends Loader {
    use WithLocations;
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
            $reseller = Organization::query()->whereKey($id)->first();

            if ($reseller) {
                $this->logger->error('Reseller found in database but not found in Cosmos.', [
                    'id' => $id,
                ]);
            }

            return false;
        }

        // Load customer
        $resellers = $this->getOrganizationFactory();
        $reseller  = $resellers->create($company);

        if ($this->isWithAssets()) {
            $this->loadAssets($reseller);
        }

        $this->updateResellerCountable($reseller);

        // Return
        return true;
    }
    // </editor-fold>

    // =========================================================================
    protected function getCurrentAssets(Model $owner): QueryIterator {
        return $this->client->getAssetsByResellerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getMissedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Organization
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    //</editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function getOrganizationFactory(): OrganizationFactory {
        return (clone $this->resellers)
            ->setLocationFactory(
                $this->isWithLocations() ? $this->locations : null,
            );
    }
    // </editor-fold>
}
