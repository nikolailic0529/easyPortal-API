<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\AssetNotFoundException;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\LoaderRecalculable;
use App\Services\DataLoader\Loaders\Concerns\WithCalculatedProperties;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Exception;
use Psr\Log\LoggerInterface;

class AssetLoader extends Loader implements LoaderRecalculable {
    use WithCalculatedProperties;

    protected bool $withDocuments = false;

    public function __construct(
        LoggerInterface $logger,
        Client $client,
        protected AssetFactory $assets,
        protected DocumentFactory $documents,
        protected ResellerResolver $resellers,
        protected CustomerResolver $customers,
    ) {
        parent::__construct($logger, $client);
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

    /**
     * @inheritDoc
     */
    protected function getResolversToRecalculate(): array {
        return [$this->resellers, $this->customers];
    }
}
