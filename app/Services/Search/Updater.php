<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\ModelHelper;
use Closure;
use DateTimeInterface;
use Elasticsearch\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Events\ModelsImported;
use Laravel\Telescope\Telescope;
use LastDragon_ru\LaraASP\Eloquent\Iterators\ChunkedChangeSafeIterator;
use Throwable;

use function array_filter;
use function array_keys;
use function array_values;

class Updater {
    use GlobalScopes;

    protected ?Closure $onInit   = null;
    protected ?Closure $onChange = null;
    protected ?Closure $onFinish = null;

    public function __construct(
        protected Repository $config,
        protected Dispatcher $dispatcher,
        protected Client $client,
    ) {
        // empty
    }

    protected function getConfig(): Repository {
        return $this->config;
    }

    protected function getDispatcher(): Dispatcher {
        return $this->dispatcher;
    }

    protected function getClient(): Client {
        return $this->client;
    }

    public function onInit(?Closure $closure): static {
        $this->onInit = $closure;

        return $this;
    }

    public function onChange(?Closure $closure): static {
        $this->onChange = $closure;

        return $this;
    }

    public function onFinish(?Closure $closure): static {
        $this->onFinish = $closure;

        return $this;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param array<string|int>|null                                                                     $ids
     */
    public function update(
        string $model,
        DateTimeInterface $from = null,
        string|int $continue = null,
        int $chunk = null,
        array $ids = null,
    ): void {
        $this->call(function () use ($model, $from, $continue, $chunk, $ids): void {
            $index    = $this->createIndex($model);
            $status   = new Status($from, $continue, $this->getTotal($model, $from, $ids));
            $iterator = $this
                ->getIterator($model, $from, $chunk, $continue, $ids)
                ->onBeforeChunk(function (Collection $items) use ($status): void {
                    $this->onBeforeChunk($items, $status);
                })
                ->onAfterChunk(function (Collection $items) use (&$iterator, $status): void {
                    $this->onAfterChunk($items, $status, $iterator->getOffset());
                });

            $this->onBeforeImport($status);

            foreach ($iterator as $item) {
                /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable $item */
                try {
                    $isTrashed                   = $item->trashed();
                    $isUnsearchable              = !$item->shouldBeSearchable();
                    $isSoftDeletableModel        = (new ModelHelper($item))->isSoftDeletable();
                    $isSoftDeletableIndexed      = (bool) $this->getConfig()->get('scout.soft_delete', false);
                    $isSoftDeletableUnsearchable = $isSoftDeletableModel && !$isSoftDeletableIndexed && $isTrashed;

                    if ($isUnsearchable || $isSoftDeletableUnsearchable) {
                        $item->setSearchableAs($index)->unsearchable();
                    } else {
                        $item->setSearchableAs($index)->searchable();
                    }
                } catch (Throwable $exception) {
                    // TODO: Use Exception + handler
                    $this->logger->warning('Failed to add object into search index.', [
                        'importer'  => $this::class,
                        'object'    => $item,
                        'exception' => $exception,
                    ]);
                } finally {
                    $status->processed++;
                }
            }

            $this->switchIndex($model);
            $this->onAfterImport($status);
        });
    }

    private function call(Closure $closure): void {
        $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($closure): void {
            $this->callWithoutScoutQueue(static function () use ($closure): void {
                // Telescope should be disabled because it stored all data in memory
                // and will dump it only after the job/command/request is finished.
                // For long-running jobs, this will lead to huge memory usage

                Telescope::withoutRecording($closure);
            });
        });
    }

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

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<string|int>|null                            $ids
     */
    protected function getIterator(
        string $model,
        ?DateTimeInterface $from,
        ?int $chunk,
        int|string|null $continue,
        array|null $ids,
    ): ChunkedChangeSafeIterator {
        $chunk    = $chunk ?? $this->getConfig()->get('scout.chunk.searchable') ?? null;
        $iterator = $this->getBuilder($model, $from, $ids)
            ->when(true, static function (Builder $builder): void {
                $builder->newModelInstance()->makeAllSearchableUsing($builder);
            })
            ->getChangeSafeIterator()
            ->setLimit(null);

        if ($chunk) {
            $iterator->setChunkSize($chunk);
        }

        if ($continue) {
            $iterator->setOffset($continue);
        }

        return $iterator;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<string|int>|null                            $ids
     */
    protected function getBuilder(string $model, DateTimeInterface $from = null, array $ids = null): Builder {
        $trashed = (new ModelHelper($model))->isSoftDeletable();
        $builder = $model::query()
            ->when($ids, static function (Builder $builder) use ($ids): void {
                $builder->whereIn($builder->getModel()->getKeyName(), $ids);
            })
            ->when($from, static function (Builder $builder) use ($from): void {
                $builder->where($builder->getModel()->getUpdatedAtColumn(), '>=', $from);
            })
            ->when($trashed, static function (Builder $builder): void {
                $builder->withTrashed();
            });

        return $builder;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<string|int>|null                            $ids
     */
    protected function getTotal(string $model, DateTimeInterface $from = null, array $ids = null): ?int {
        return $this->getBuilder($model, $from, $ids)->count();
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     */
    public function isIndexActual(string $model): bool {
        $client = $this->getClient()->indices();
        $config = (new $model())->getSearchConfiguration();
        $alias  = $config->getIndexAlias();
        $index  = $config->getIndexName();

        return $client->exists(['index' => $index])
            && $client->existsAlias(['name' => $alias, 'index' => $index]);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     */
    protected function createIndex(string $model): string {
        $client = $this->getClient()->indices();
        $config = (new $model())->getSearchConfiguration();
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

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     */
    protected function switchIndex(string $model): void {
        // Prepare
        $client  = $this->getClient()->indices();
        $config  = (new $model())->getSearchConfiguration();
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

    protected function onBeforeChunk(Collection $items, Status $status): void {
        // Empty
    }

    protected function onAfterChunk(Collection $items, Status $status, string|int|null $continue): void {
        // Event (needed for scout:import)
        $this->getDispatcher()->dispatch(new ModelsImported($items));

        // Update status
        $status->continue = $continue;
        $status->chunk++;

        // Call callback
        if ($this->onChange) {
            ($this->onChange)($items, clone $status);
        }
    }

    protected function onBeforeImport(Status $status): void {
        if ($this->onInit) {
            ($this->onInit)(clone $status);
        }
    }

    protected function onAfterImport(Status $status): void {
        if ($this->onFinish) {
            ($this->onFinish)(clone $status);
        }
    }
}
