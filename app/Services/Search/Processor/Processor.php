<?php declare(strict_types = 1);

namespace App\Services\Search\Processor;

use App\Services\Search\Configuration;
use App\Services\Search\Exceptions\FailedToIndex;
use App\Services\Search\Exceptions\IndexError;
use App\Utils\Eloquent\ModelHelper;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\State as ProcessorState;
use Closure;
use Elasticsearch\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Events\ModelsImported;
use LogicException;
use Throwable;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_values;

/**
 * @template TItem of \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
 * @template TState of \App\Services\Search\Processor\State<TItem>
 *
 * @extends \App\Utils\Processor\EloquentProcessor<TItem, null, TState>
 */
class Processor extends EloquentProcessor {
    /**
     * @var class-string<TItem>
     */
    private string $model;
    private bool   $rebuild;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        private Repository $config,
        private Client $client,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getConfig(): Repository {
        return $this->config;
    }

    protected function getClient(): Client {
        return $this->client;
    }

    public function getModel(): string {
        return $this->model;
    }

    /**
     * @param class-string<TItem> $model
     */
    public function setModel(string $model): static {
        $this->model = $model;

        return $this;
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

    // <editor-fold desc="Processor">
    // =========================================================================
    protected function init(ProcessorState $state): void {
        if ($state->rebuild) {
            $state->name = $this->createIndex($state);
        }

        parent::init($state);
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
     * @param \App\Services\Search\Processor\State<TItem>                                  $state
     * @param \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $item
     */
    protected function process(ProcessorState $state, mixed $data, mixed $item): void {
        try {
            $as                          = $item->searchableAs();
            $item                        = $item->setSearchableAs($state->name);
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
        $this->getExceptionHandler()->report(
            $item
                ? new FailedToIndex($this, $item, $exception)
                : new IndexError($this, $exception),
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
    protected function restoreState(array $state): State {
        return new State($state);
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
     * todo(!): Not needed?
     */
    public function isIndexActual(State $state): bool {
        $client = $this->getClient()->indices();
        $config = $this->getSearchConfiguration($state);
        $alias  = $config->getIndexAlias();
        $index  = $config->getIndexName();

        return $client->exists(['index' => $index])
            && $client->existsAlias(['name' => $alias, 'index' => $index]);
    }

    protected function createIndex(State $state): string {
        $client = $this->getClient()->indices();
        $config = $this->getSearchConfiguration($state);
        $alias  = $config->getIndexAlias();
        $index  = $config->getIndexName();

        if ($client->exists(['index' => $alias]) && !$client->existsAlias(['name' => $alias])) {
            $client->delete(['index' => $alias]);
        }

        if (!$client->exists(['index' => $index])) {
            $client->create([
                'index' => $index,
                'body'  => [
                    'mappings' => $config->getMappings(),
                ],
            ]);
        }

        if (!$client->existsAlias(['name' => $alias])) {
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

    protected function switchIndex(State $state): void {
        // Prepare
        $client  = $this->getClient()->indices();
        $config  = $this->getSearchConfiguration($state);
        $alias   = $config->getIndexAlias();
        $index   = $config->getIndexName();
        $indexes = array_keys($client->getAlias());
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
    protected function getSearchConfiguration(State $state): Configuration {
        $model  = $state->model;
        $config = (new $model())->getSearchConfiguration();

        return $config;
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function call(Closure $callback): mixed {
        return $this->callWithoutScoutQueue(function () use ($callback): mixed {
            return parent::call($callback);
        });
    }

    /**
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    private function callWithoutScoutQueue(Closure $closure): mixed {
        $key      = 'scout.queue';
        $config   = $this->getConfig();
        $previous = $config->get($key);

        try {
            $config->set($key, false);

            return $closure();
        } finally {
            $config->set($key, $previous);
        }
    }
    // </editor-fold>
}
