<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Model;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Schema\Type;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Psr\Log\LoggerInterface;

use function is_array;

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

    /**
     * @param string|array<string,mixed> $object
     */
    public function load(string|array $object): ?Model {
        $object = is_array($object) ? $this->getObject($object) : $this->getObjectById($object);
        $object = $this->callWithoutGlobalScopes([OwnedByOrganizationScope::class], function () use ($object): ?Model {
            return $this->process($object);
        });

        return $object;
    }

    abstract protected function getObjectById(string $id): ?Type;

    /**
     * @param array<string,mixed> $properties
     */
    abstract protected function getObject(array $properties): ?Type;

    abstract protected function process(?Type $object): ?Model;
}
