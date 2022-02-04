<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Factory\Factories\DistributorFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

class DistributorLoader extends Loader {
    public function __construct(
        Container $container,
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Client $client,
        Collector $collector,
        protected DistributorFactory $distributors,
    ) {
        parent::__construct($container, $exceptionHandler, $dispatcher, $client, $collector);
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new Company($properties);
    }

    protected function getObjectById(string $id): ?Type {
        return $this->client->getDistributorById($id);
    }

    protected function getObjectFactory(): ModelFactory {
        return $this->distributors;
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new DistributorNotFound($id);
    }
}
