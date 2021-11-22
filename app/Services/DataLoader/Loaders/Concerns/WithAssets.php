<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAsset;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loaders\AssetLoader;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\QueryIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * @mixin \App\Services\DataLoader\Loader
 */
trait WithAssets {
    use AssetsPrefetch;
    use WithCalculatedProperties;

    protected bool $withAssets          = false;
    protected bool $withAssetsDocuments = false;

    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Client $client,
        protected ResellerFactory $resellerFactory,
        protected CustomerFactory $customerFactory,
        protected LocationFactory $locationFactory,
        protected ContactFactory $contactFactory,
        protected AssetFactory $assetFactory,
        protected AssetLoader $assetLoader,
        protected DocumentFactory $documentFactory,
    ) {
        parent::__construct($container, $exceptionHandler, $client);
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
        $prefetch = function (array $assets): void {
            $this->prefetchAssets($assets);
        };

        foreach ($this->getCurrentAssets($owner)->onBeforeChunk($prefetch) as $asset) {
            try {
                $model = $factory->create($asset);

                if ($model) {
                    $updated[] = $model->getKey();
                }
            } catch (Throwable $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessViewAsset($asset, $exception),
                );
            }
        }

        // Update missed
        $loader   = $this->getAssetLoader();
        $iterator = $this->getMissedAssets($owner, $updated)?->getChangeSafeIterator() ?? [];

        unset($updated);

        foreach ($iterator as $missed) {
            /** @var \App\Models\Asset $missed */
            try {
                $loader->update($missed->getKey());
            } catch (Throwable $exception) {
                $this->getExceptionHandler()->report($exception);
            }
        }

        // Return
        return true;
    }

    /**
     * @return \App\Utils\Iterators\QueryIterator<\App\Services\DataLoader\Schema\ViewAsset>
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
            $this->assetFactory->setDocumentFactory($this->documentFactory);
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
        return [
            ResellerResolver::class,
            CustomerResolver::class,
            LocationResolver::class,
        ];
    }
    // </editor-fold>
}
