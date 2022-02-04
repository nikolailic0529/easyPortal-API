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
use App\Services\DataLoader\Schema\TypeWithId;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\Model;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

use function is_string;

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
    /**
     * @param array<string,mixed> $properties
     */
    abstract protected function getObject(array $properties): ?Type;

    abstract protected function getObjectById(string $id): ?Type;

    abstract protected function getObjectFactory(): ModelFactory;

    abstract protected function getModelNotFoundException(string $id): Exception;
    // </editor-fold>

    // <editor-fold desc="Load">
    // =========================================================================
    public function update(Type|string $object): ?Model {
        return GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($object): ?Model {
            if (is_string($object)) {
                if ($this->getObject([]) instanceof TypeWithId && !$this->isModelExists($object)) {
                    throw $this->getModelNotFoundException($object);
                } else {
                    $object = $this->getObjectById($object);
                }
            } else {
                if ($object instanceof TypeWithId && !$this->isModelExists($object->id)) {
                    throw $this->getModelNotFoundException($object->id);
                }
            }

            return $this->run($object);
        });
    }

    public function create(Type|string $object): ?Model {
        return GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($object): ?Model {
            $object = is_string($object) ? $this->getObjectById($object) : $object;
            $model  = $this->run($object);

            return $model;
        });
    }

    protected function run(?Type $object): ?Model {
        // Object?
        if (!$object) {
            return null;
        }

        // Subscribe
        $data = new ImporterChunkData([$object]);

        $this->getCollector()->subscribe($data);

        // Process
        try {
            $model = $this->process($object);
        } finally {
            if ($this instanceof LoaderRecalculable) {
                $this->recalculate();
            }

            $this->getDispatcher()->dispatch(
                new DataImported($data),
            );
        }

        return $model;
    }

    protected function process(Type $object): ?Model {
        return $this->getObjectFactory()->create($object);
    }

    protected function isModelExists(string $id): bool {
        return (bool) $this->getObjectFactory()->find($this->getObject(['id' => $id]));
    }
    // </editor-fold>
}
