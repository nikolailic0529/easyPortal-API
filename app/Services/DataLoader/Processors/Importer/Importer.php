<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Exceptions\FailedToImportObject;
use App\Services\DataLoader\Exceptions\ImportError;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Processors\Concerns\WithForce;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\IteratorProcessor;
use App\Utils\Processor\State;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Throwable;

use function array_map;
use function array_merge;

/**
 * @template TItem of (Type&TypeWithKey)|ModelObject
 * @template TChunkData of Data
 * @template TState of ImporterState
 * @template TModel of Model
 *
 * @extends IteratorProcessor<TItem, TChunkData, TState>
 */
abstract class Importer extends IteratorProcessor implements Isolated {
    use WithForce;

    private ?ImporterCollectedData $collectedData = null;
    private Collector              $collector;

    /**
     * @var Factory<TModel>
     */
    private Factory $factory;

    /**
     * @var Resolver<TModel>
     */
    private Resolver $resolver;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        private Client $client,
        private Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);

        $this->register();
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getClient(): Client {
        return $this->client;
    }

    protected function getContainer(): Container {
        return $this->container;
    }

    public function getFrom(): ?DateTimeInterface {
        return null;
    }

    /**
     * @return Resolver<TModel>
     */
    protected function getResolver(): Resolver {
        return $this->resolver;
    }

    /**
     * @return Factory<TModel>
     */
    protected function getFactory(): Factory {
        return $this->factory;
    }
    // </editor-fold>

    // <editor-fold desc="Import">
    // =========================================================================
    protected function init(State $state, ObjectIterator $iterator): void {
        $this->resetCollectedData();

        parent::init($state, $iterator);
    }

    protected function finish(State $state): void {
        parent::finish($state);

        $this->resetCollectedData();
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        $this->getExceptionHandler()->report(
            $item
                ? new FailedToImportObject($this, $item, $exception)
                : new ImportError($this, $exception),
        );
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data   = $this->makeData($items);
        $model  = $this->getFactory()->getModel();
        $models = $this->getResolver()
            ->prefetch($data->get($model))
            ->getResolved();

        $this->preload($state, $data, $models);

        return $data;
    }

    /**
     * @param TState $state
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        // Model?
        if ($item instanceof ModelObject) {
            $item->model->delete();
            $state->deleted++;

            return;
        }

        // Import
        /** @phpstan-ignore-next-line todo(DataLoader): would be good to use interface */
        if ($this->getResolver()->get($item->id)) {
            $this->getFactory()->create($item, $state->force);
            $state->updated++;
        } else {
            $this->getFactory()->create($item, $state->force);
            $state->created++;
        }
    }

    /**
     * @inheritDoc
     */
    protected function chunkLoaded(State $state, array $items): mixed {
        // Reset objects
        $this->collector = $this->getContainer()->make(Collector::class);
        $this->resolver  = $this->makeResolver($state);
        $this->factory   = $this->makeFactory($state);

        // Configure
        $data = $this->makeData([]);

        $this->collector->subscribe($data);

        // Parent
        parent::chunkLoaded($state, $items);

        // Return
        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function chunkProcessed(State $state, array $items, mixed $data): void {
        // Mark as synchronized
        // The `withoutTimestamps` method is used because we just need to mark
        // when the model was synchronized (on another side we know nothing about
        // properties changes, thus setting the `updated_at` doesn't look good).
        $class = $this->getFactory()->getModel();
        $model = new $class();
        $keys  = array_map(static fn($item) => $item->getKey(), $items);

        if ($keys) {
            $class::withoutTimestamps(static function () use ($model, $keys): void {
                $model::query()
                    ->whereIn($model->getKeyName(), $keys)
                    ->update([
                        'synced_at' => Date::now(),
                    ]);
            });
        }

        // Reset container
        $this->getContainer()->forgetInstances();

        // Unset
        unset($this->collector);
        unset($this->resolver);
        unset($this->factory);

        // Parent
        parent::chunkProcessed($state, $items, $data);
    }

    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        $threshold = $this->getChunkSize();
        $collected = $this->getCollectedData()->collect($threshold, $data);
        $event     = $collected ? new DataImported($collected) : null;

        return $event;
    }

    protected function getOnFinishEvent(State $state): ?object {
        $data  = $this->getCollectedData()->getData();
        $event = $data ? new DataImported($data) : null;

        return $event;
    }
    // </editor-fold>

    // <editor-fold desc="Data">
    // =========================================================================
    private function getCollectedData(): ImporterCollectedData {
        $this->collectedData ??= new ImporterCollectedData();

        return $this->collectedData;
    }

    private function resetCollectedData(): void {
        $this->collectedData = null;
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new ImporterState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'from'  => $this->getFrom(),
            'force' => $this->isForce(),
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="Abstract">
    // =========================================================================
    abstract protected function register(): void;

    /**
     * @param TState                  $state
     * @param TChunkData              $data
     * @param Collection<int, TModel> $models
     */
    abstract protected function preload(State $state, Data $data, Collection $models): void;

    /**
     * @param array<TItem> $items
     *
     * @return TChunkData
     */
    abstract protected function makeData(array $items): mixed;

    /**
     * @param TState $state
     *
     * @return Factory<TModel>
     */
    abstract protected function makeFactory(State $state): Factory;

    /**
     * @param TState $state
     *
     * @return Resolver<TModel>
     */
    abstract protected function makeResolver(State $state): Resolver;
    // </editor-fold>
}
