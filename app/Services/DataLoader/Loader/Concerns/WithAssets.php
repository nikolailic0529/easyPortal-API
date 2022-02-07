<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Factory\Factories\ContactFactory;
use App\Services\DataLoader\Factory\Factories\CustomerFactory;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\Factories\LocationFactory;
use App\Services\DataLoader\Factory\Factories\ResellerFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\EloquentIterator;
use DateTimeInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

/**
 * @template TOwner of \App\Utils\Eloquent\Model
 *
 * @mixin \App\Services\DataLoader\Loader\Loader
 */
trait WithAssets {
    use WithCalculatedProperties;

    protected bool $withAssets          = false;
    protected bool $withAssetsDocuments = false;

    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Client $client,
        Collector $collector,
        protected ResellerFactory $resellerFactory,
        protected CustomerFactory $customerFactory,
        protected LocationFactory $locationFactory,
        protected ContactFactory $contactFactory,
        protected DocumentFactory $documentFactory,
    ) {
        parent::__construct($container, $exceptionHandler, $dispatcher, $client, $collector);
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

    /**
     * @param TOwner $owner
     */
    protected function loadAssets(Model $owner): bool {
        // Update assets
        $date = Date::now();

        $this
            ->getAssetsImporter($owner)
            ->setWithDocuments($this->isWithAssetsDocuments())
            ->setFrom(null)
            ->setLimit(null)
            ->setChunkSize(null)
            ->start();

        // Update missed
        $iterator = $this->getMissedAssets($owner, $date)->getChangeSafeIterator();
        $iterator = new EloquentIterator($iterator);

        $this
            ->getContainer()
            ->make(AssetsIteratorImporter::class)
            ->setIterator($iterator)
            ->setFrom(null)
            ->setLimit(null)
            ->setChunkSize(null)
            ->start();

        // Return
        return true;
    }

    /**
     * @param TOwner $owner
     */
    abstract protected function getAssetsImporter(Model $owner): AssetsImporter;

    /**
     * @param TOwner $owner
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Asset>
     */
    abstract protected function getMissedAssets(Model $owner, DateTimeInterface $datetime): Builder;

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
