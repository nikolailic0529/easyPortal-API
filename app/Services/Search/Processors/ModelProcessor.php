<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Services\Search\Configuration;
use App\Services\Search\Elastic\Elastic;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Exceptions\ElasticReadonly;
use App\Services\Search\Exceptions\ElasticUnavailable;
use App\Services\Search\Exceptions\FailedToIndex;
use App\Services\Search\Exceptions\ProcessorError;
use App\Services\Search\Processors\Concerns\WithModel;
use App\Utils\Eloquent\ModelHelper;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\State;
use App\Utils\Processor\State as ProcessorState;
use Closure;
use Elastic\Adapter\Exceptions\BulkOperationException;
use Elastic\Elasticsearch\Client;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Events\ModelsImported;
use LogicException;
use Throwable;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_values;
use function mb_strpos;

/**
 * @template TItem of Model&Searchable
 * @template TState of ModelProcessorState<TItem>
 *
 * @extends EloquentProcessor<TItem, null, TState>
 */
class ModelProcessor extends EloquentProcessor {
    /**
     * @use WithModel<TItem>
     */
    use WithModel;

    private bool $rebuild = false;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        private Client $client,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getClient(): Client {
        return $this->client;
    }

    public function isRebuild(): bool {
        return $this->rebuild;
    }

    public function setRebuild(bool $rebuild): static {
        $this->rebuild = $rebuild;

        return $this;
    }

    public function isWithTrashed(): bool {
        return true;
    }

    public function getDefaultChunkSize(): int {
        return (int) $this->getConfig()->get('scout.chunk.searchable')
            ?: parent::getDefaultChunkSize();
    }
    // </editor-fold>

    // <editor-fold desc="Processors">
    // =========================================================================
    protected function init(ProcessorState $state, ObjectIterator $iterator): void {
        if ($state->rebuild) {
            $state->name = $this->createIndex($state);
        }

        parent::init($state, $iterator);
    }

    protected function finish(ProcessorState $state): void {
        if ($state->rebuild) {
            $this->switchIndex($state);
        }

        parent::finish($state);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(ProcessorState $state, array $items): mixed {
        return null;
    }

    /**
     * @param ModelProcessorState<TItem> $state
     * @param Model&Searchable           $item
     */
    protected function process(ProcessorState $state, mixed $data, mixed $item): void {
        $as   = $item->searchableAs();
        $item = $item->setSearchableAs($state->name);

        try {
            $isUnsearchable              = !$item->shouldBeSearchable();
            $isSoftDeletableModel        = (new ModelHelper($item))->isSoftDeletable();
            $isSoftDeletableIndexed      = (bool) $this->getConfig()->get('scout.soft_delete', false);
            $isSoftDeletableUnsearchable = !$isSoftDeletableIndexed && $isSoftDeletableModel && $item->trashed();

            if ($isUnsearchable || $isSoftDeletableUnsearchable) {
                $item->unsearchable();
            } else {
                $item->searchable();
            }
        } finally {
            $item->setSearchableAs($as);
        }
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        // If Elasticsearch unavailable we cannot do anything -> break
        if ($exception instanceof NoNodeAvailableException) {
            throw new ElasticUnavailable($exception);
        }

        // If operation failed -> something may be really wrong -> break
        if (
            $exception instanceof BulkOperationException
            && mb_strpos($exception->getMessage(), 'disk usage exceeded') !== false
        ) {
            throw new ElasticReadonly($exception);
        }

        // Report
        $this->getExceptionHandler()->report(
            $item
                ? new FailedToIndex($this, $item, $exception)
                : new ProcessorError($this, $exception),
        );
    }

    protected function getBuilder(ProcessorState $state): Builder {
        $builder = parent::getBuilder($state);
        $builder = $builder->newModelInstance()->makeAllSearchableUsing($builder);

        return $builder;
    }
    //</editor-fold>

    // <editor-fold desc="Events">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(ProcessorState $state, array $items, mixed $data): ?object {
        // Also needed for `scout:import`
        return new ModelsImported(new Collection($items));
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): ModelProcessorState {
        return new ModelProcessorState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        if ($this->isRebuild() && $this->getKeys() !== null) {
            throw new LogicException('Rebuild is not possible because keys are specified.');
        }

        return array_merge(parent::defaultState($state), [
            'rebuild' => $this->isRebuild(),
            'name'    => null,
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="Index">
    // =========================================================================
    /**
     * @param TState $state
     */
    protected function createIndex(ModelProcessorState $state): string {
        $client   = $this->getClient()->indices();
        $config   = $this->getSearchConfiguration($state);
        $alias    = $config->getIndexAlias();
        $index    = $config->getIndexName();
        $hasIndex = Elastic::response($client->exists(['index' => $alias]))->asBool()
            && !Elastic::response($client->existsAlias(['name' => $alias]))->asBool();

        if ($hasIndex) {
            $client->delete(['index' => $alias]);
        }

        if (!Elastic::response($client->exists(['index' => $index]))->asBool()) {
            $client->create([
                'index' => $index,
                'body'  => [
                    'mappings' => $config->getMappings(),
                ],
            ]);
        }

        if (!Elastic::response($client->existsAlias(['name' => $alias]))->asBool()) {
            $client->putAlias([
                'name'  => $alias,
                'index' => $index,
                'body'  => [
                    'is_write_index' => true,
                ],
            ]);
        }

        return $index;
    }

    /**
     * @param TState $state
     */
    protected function switchIndex(ModelProcessorState $state): void {
        // Prepare
        $client  = $this->getClient()->indices();
        $config  = $this->getSearchConfiguration($state);
        $alias   = $config->getIndexAlias();
        $index   = $config->getIndexName();
        $indexes = array_keys(Elastic::response($client->getAlias())->asArray());
        $indexes = array_values(array_filter($indexes, static function (string $name) use ($config, $index): bool {
            return $name !== $index && $config->isIndex($name);
        }));

        // Update
        $actions = array_values(array_filter([
            // Add alias to new index
            [
                'add' => [
                    'index'          => $index,
                    'alias'          => $alias,
                    'is_write_index' => true,
                ],
            ],
            // Remove old indexes
            $indexes ? [
                'remove_index' => [
                    'indices' => $indexes,
                ],
            ] : null,
        ]));

        $client->updateAliases([
            'body' => [
                'actions' => $actions,
            ],
        ]);
    }

    /**
     * @param TState $state
     */
    protected function getSearchConfiguration(ModelProcessorState $state): Configuration {
        $model  = $state->model;
        $config = (new $model())->getSearchConfiguration();

        return $config;
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @template T
     *
     * @param TState       $state
     * @param Closure(): T $callback
     *
     * @return   (T is void ? null : T)
     */
    protected function call(State $state, Closure $callback): mixed {
        return $this->callWithoutScoutQueue(function () use ($state, $callback): mixed {
            return parent::call($state, $callback);
        });
    }

    /**
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    private function callWithoutScoutQueue(Closure $callback): mixed {
        $key      = 'scout.queue';
        $config   = $this->getConfig();
        $previous = $config->get($key);

        try {
            $config->set($key, false);

            return $callback();
        } finally {
            $config->set($key, $previous);
        }
    }
    // </editor-fold>
}
