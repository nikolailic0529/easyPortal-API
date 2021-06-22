<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithId;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Exception;
use Psr\Log\LoggerInterface;

use function is_string;

/**
 * Load data from API and create app's objects. You must use
 * {@link \App\Services\DataLoader\DataLoaderService} to obtain instance.
 *
 * @internal
 */
abstract class Loader implements Isolated {
    use GlobalScopes;

    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
    ) {
        // empty
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

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
        return $this->callWithoutGlobalScopes([OwnedByOrganizationScope::class], function () use ($object): ?Model {
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
        return $this->callWithoutGlobalScopes([OwnedByOrganizationScope::class], function () use ($object): ?Model {
            $object = is_string($object) ? $this->getObjectById($object) : $object;
            $model  = $this->run($object);

            return $model;
        });
    }

    private function run(?Type $object): ?Model {
        try {
            return $this->process($object);
        } finally {
            if ($this instanceof LoaderRecalculable) {
                $this->recalculate();
            }
        }
    }

    protected function process(?Type $object): ?Model {
        return $object
            ? $this->getObjectFactory()->create($object)
            : null;
    }

    protected function isModelExists(string $id): bool {
        return (bool) $this->getObjectFactory()->find($this->getObject(['id' => $id]));
    }
    // </editor-fold>
}
