<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\DocumentNotFound;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

class DocumentLoader extends Loader {
    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Client $client,
        Collector $collector,
        protected DocumentFactory $documentFactory,
    ) {
        parent::__construct($container, $exceptionHandler, $dispatcher, $client, $collector);
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
}
