<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Factory\Factories\AssetFactory;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Loader\Concerns\WithWarrantyCheck;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

class AssetLoader extends Loader {
    use WithWarrantyCheck;

    protected bool $withDocuments = false;

    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Client $client,
        Collector $collector,
        protected AssetFactory $assets,
        protected DocumentFactory $documents,
    ) {
        parent::__construct($container, $exceptionHandler, $dispatcher, $client, $collector);
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
        if ($this->isWithWarrantyCheck()) {
            $this->runAssetWarrantyCheck($id);
        }

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
        return new AssetNotFound($id);
    }
}
