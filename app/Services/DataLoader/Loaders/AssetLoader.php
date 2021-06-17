<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\AssetNotFoundException;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;

class AssetLoader extends Loader {
    protected bool $withDocuments = false;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected Dispatcher $dispatcher,
        protected AssetFactory $assets,
        protected DocumentFactory $documents,
        protected ResellerResolver $resellerResolver,
        protected ResellerLoader $resellerLoader,
        protected CustomerResolver $customerResolver,
        protected CustomerLoader $customerLoader,
        protected DistributorResolver $distributorResolver,
        protected DistributorLoader $distributorLoader,
    ) {
        parent::__construct($logger, $client);

        $this->resellerResolver->setFinder($this->resellerLoader);
        $this->customerResolver->setFinder($this->customerLoader);
        $this->distributorResolver->setFinder($this->distributorLoader);
    }

    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new ViewAsset($properties);
    }

    protected function getObjectById(string $id): ?Type {
        return $this->isWithDocuments()
            ? $this->client->getAssetByIdWithDocuments($id)
            : $this->client->getAssetById($id);
    }

    protected function getObjectFactory(): ModelFactory {
        if ($this->isWithDocuments()) {
            $this->assets->setDocumentFactory($this->documents);
        }

        return $this->assets;
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new AssetNotFoundException($id);
    }

    protected function process(?Type $object): ?Model {
        try {
            return parent::process($object);
        } finally {
            // todo: Calculated properties
        }
    }
}
