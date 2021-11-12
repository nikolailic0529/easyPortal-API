<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\DocumentNotFound;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\LoaderRecalculable;
use App\Services\DataLoader\Loaders\Concerns\WithCalculatedProperties;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

class DocumentLoader extends Loader implements LoaderRecalculable {
    use WithCalculatedProperties;

    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Client $client,
        protected DocumentFactory $documentFactory,
    ) {
        parent::__construct($container, $exceptionHandler, $client);
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new Document($properties);
    }

    protected function getObjectById(string $id): ?Type {
        return $this->client->getDocumentById($id);
    }

    protected function getObjectFactory(): ModelFactory {
        return $this->documentFactory;
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new DocumentNotFound($id);
    }

    /**
     * @inheritDoc
     */
    protected function getResolversToRecalculate(): array {
        return [
            ResellerResolver::class,
        ];
    }
}
