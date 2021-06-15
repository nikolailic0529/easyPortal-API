<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Model;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class ResellerLoader extends Loader {
    use GlobalScopes;
    use WithAssets;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected Dispatcher $dispatcher,
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
        // Process
        $this->callWithoutGlobalScopes([OwnedByOrganizationScope::class], function () use ($id): void {
            $this->process($id);
        });

        // Return
        return true;
    }

    protected function process(string $id): void {
        $reseller = null;

        try {
            $company = $this->client->getCompanyById($id);

            if ($company) {
                $factory  = $this->getResellersFactory();
                $reseller = $factory->find($company);

                if ($reseller) {
                    $reseller = $factory->create($company);

                    if ($this->isWithAssets()) {
                        $this->loadAssets($reseller);
                    }
                }
            } else {
                $reseller = Reseller::query()->whereKey($id)->first();

                if ($reseller) {
                    $this->logger->warning('Reseller found in database but not found in Cosmos.', [
                        'id' => $id,
                    ]);
                }
            }

            if (!$reseller) {
                throw new ResellerNotFoundException($id, $company);
            }
        } finally {
            if ($reseller) {
                $this->updateResellerCalculatedProperties($reseller);
            }
        }
    }
    // </editor-fold>

    // =========================================================================
    protected function getCurrentAssets(Model $owner): OffsetBasedIterator {
        return $this->isWithAssetsDocuments()
            ? $this->client->getAssetsByResellerIdWithDocuments($owner->getKey())
            : $this->client->getAssetsByResellerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getMissedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Reseller
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    //</editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function getResellersFactory(): ResellerFactory {
        return $this->resellers;
    }
    // </editor-fold>
}
