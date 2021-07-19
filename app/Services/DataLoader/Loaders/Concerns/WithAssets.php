<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\DataLoader\Exceptions\AssetNotFoundException;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loaders\AssetLoader;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @mixin \App\Services\DataLoader\Loader
 */
trait WithAssets {
    use WithCalculatedProperties;

    protected bool $withAssets          = false;
    protected bool $withAssetsDocuments = false;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected Dispatcher $dispatcher,
        protected ResellerFactory $resellerFactory,
        protected ResellerResolver $resellerResolver,
        protected CustomerFactory $customerFactory,
        protected CustomerResolver $customerResolver,
        protected LocationFactory $locations,
        protected ContactFactory $contacts,
        protected AssetFactory $assetFactory,
        protected AssetLoader $assetLoader,
        protected DocumentFactory $documents,
    ) {
        parent::__construct($logger, $client);
    }

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
        $factory  = $this->getAssetsFactory();
        $updated  = [];
        $prefetch = function (array $assets) use ($factory): void {
            $factory->prefetch($assets, true, function (Collection $assets): void {
                if ($this->isWithAssetsDocuments()) {
                    $assets->loadMissing('warranties');
                    $assets->loadMissing('warranties.serviceLevels');
                }
            });
            $this->getCustomersFactory()?->prefetch($assets, false);
            $this->getResellersFactory()?->prefetch($assets, false);
            $factory->getDocumentFactory()?->prefetch($assets, false, static function (Collection $documents): void {
                $documents->loadMissing('entries');
                $documents->loadMissing('entries.serviceLevel');
            });
        };

        foreach ($this->getCurrentAssets($owner)->onBeforeChunk($prefetch) as $asset) {
            try {
                $model = $factory->create($asset);

                if ($model) {
                    $updated[] = $model->getKey();
                }
            } catch (Throwable $exception) {
                $this->dispatcher->dispatch(new ObjectSkipped($asset, $exception));
                $this->logger->notice('Failed to process Asset.', [
                    'asset'     => $asset,
                    'exception' => $exception,
                ]);
            }
        }

        // Update missed
        $loader   = $this->getAssetLoader();
        $iterator = $this->getMissedAssets($owner, $updated)?->iterator()->safe() ?? [];

        unset($updated);

        foreach ($iterator as $missed) {
            /** @var \App\Models\Asset $missed */
            try {
                $loader->update($missed->getKey());
            } catch (AssetNotFoundException $exception) {
                $this->logger->error('Asset found in database but not found in Cosmos.', [
                    'asset'     => $missed->getKey(),
                    'exception' => $exception,
                ]);
            } catch (Throwable $exception) {
                $this->logger->notice('Failed to update Asset.', [
                    'asset'     => $missed->getKey(),
                    'exception' => $exception,
                ]);
            }
        }

        // Return
        return true;
    }

    /**
     * @return \App\Services\DataLoader\Client\QueryIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    abstract protected function getCurrentAssets(Model $owner): QueryIterator;

    /**
     * @param array<string> $current
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Asset>|null
     */
    abstract protected function getMissedAssets(Model $owner, array $current): ?Builder;

    protected function getAssetsFactory(): AssetFactory {
        if ($this->isWithAssetsDocuments()) {
            $this->assetFactory->setDocumentFactory($this->documents);
        } else {
            $this->assetFactory->setDocumentFactory(null);
        }

        return $this->assetFactory;
    }

    protected function getAssetLoader(): AssetLoader {
        return $this->assetLoader;
    }

    protected function getResellersFactory(): ResellerFactory {
        return $this->resellerFactory;
    }

    protected function getCustomersFactory(): CustomerFactory {
        return $this->customerFactory;
    }

    // <editor-fold desc="WithCalculatedProperties">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getResolversToRecalculate(): array {
        return [$this->resellerResolver, $this->customerResolver];
    }
    // </editor-fold>
}
