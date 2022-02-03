<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Factory\Factories\ContactFactory;
use App\Services\DataLoader\Factory\Factories\CustomerFactory;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\Factories\LocationFactory;
use App\Services\DataLoader\Factory\Factories\ResellerFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Loader\Loaders\AssetLoader;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Utils\Eloquent\Model;
use DateTimeInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Throwable;

/**
 * @mixin \App\Services\DataLoader\Loader\Loader
 */
trait WithAssets {
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
        $date = Date::now();

        $this
            ->getAssetsImporter($owner)
            ->setWithDocuments($this->isWithAssetsDocuments())
            ->setFrom(null)
            ->setLimit(null)
            ->start();

        // Update missed
        $loader   = $this->getAssetLoader();
        $iterator = $this->getMissedAssets($owner, $date)?->getChangeSafeIterator() ?? [];

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

    abstract protected function getAssetsImporter(Model $owner): AssetsImporter;

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Asset>|null
     */
    abstract protected function getMissedAssets(Model $owner, DateTimeInterface $datetime): ?Builder;

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
