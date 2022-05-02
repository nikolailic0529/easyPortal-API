<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Schema\Type;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\Model;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Load data from API and create app's objects. You must use
 * {@link \App\Services\DataLoader\Container\Container} to obtain instance.
 *
 * @internal
 */
abstract class Loader implements Isolated {
    public function __construct(
        protected Container $container,
        protected ExceptionHandler $exceptionHandler,
        protected Dispatcher $dispatcher,
        protected Client $client,
        protected Collector $collector,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContainer(): Container {
        return $this->container;
    }

    protected function getDispatcher(): Dispatcher {
        return $this->dispatcher;
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    protected function getClient(): Client {
        return $this->client;
    }

    protected function getCollector(): Collector {
        return $this->collector;
    }
    // </editor-fold>

    // <editor-fold desc="Abstract">
    // =========================================================================
    abstract protected function getObjectById(string $id): ?Type;

    abstract protected function getObjectFactory(): ModelFactory;

    abstract protected function getModelNotFoundException(string $id): Exception;
    // </editor-fold>

    // <editor-fold desc="Load">
    // =========================================================================
    public function create(string $id): ?Model {
        return GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($id): ?Model {
            // Object
            $object = $this->getObjectById($id);

            if (!$object) {
                throw $this->getModelNotFoundException($id);
            }

            // Subscribe
            $data = new ImporterChunkData([$object]);

            $this->getCollector()->subscribe($data);

            // Process
            try {
                $model = $this->process($object);
            } finally {
                $this->getDispatcher()->dispatch(
                    new DataImported($data),
                );
            }

            return $model;
        });
    }

    protected function process(Type $object): ?Model {
        return $this->getObjectFactory()->create($object);
    }
    // </editor-fold>
}
