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
use Psr\Log\LoggerInterface;
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
        protected LoggerInterface $logger,
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

    protected function getLogger(): LoggerInterface {
        return $this->logger;
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
     */
    public function update(
        string $model,
        DateTimeInterface $from = null,
        string|int $continue = null,
        int $chunk = null,
    ): void {
        $this->call(function () use ($model, $from, $continue, $chunk): void {
            $index    = $this->createIndex($model);
            $status   = new Status($from, $continue, $this->getTotal($model, $from));
            $iterator = $this
                ->getIterator($model, $from, $chunk, $continue)
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
                    $item->setSearchableAs($index)->searchable();
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
     */
    protected function getIterator(
        string $model,
        ?DateTimeInterface $from,
        ?int $chunk,
        int|string|null $continue,
    ): ChunkedChangeSafeIterator {
        $chunk    = $chunk ?? $this->getConfig()->get('scout.chunk.searchable') ?? null;
        $iterator = $this->getBuilder($model, $from)
            ->when(true, static function (Builder $builder): void {
                $builder->newModelInstance()->makeAllSearchableUsing($builder);
            })
            ->changeSafeIterator()
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
     */
    protected function getBuilder(string $model, DateTimeInterface $from = null): Builder {
        $trashed = ModelHelper::isSoftDeletable($model)
            && $this->getConfig()->get('scout.soft_delete', false);
        $builder = $model::query()
            ->when($from, static function (Builder $builder) use ($model, $from): void {
                $builder->where((new $model())->getUpdatedAtColumn(), '>=', $from);
            })
            ->when($trashed, static function (Builder $builder): void {
                $builder->withTrashed();
            });

        return $builder;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    protected function getTotal(string $model, DateTimeInterface $from = null): ?int {
        return $this->getBuilder($model, $from)->count();
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     */
    protected function createIndex(string $model): string {
        $client = $this->getClient()->indices();
        $config = (new $model())->getSearchConfiguration();
        $alias  = $config->getIndexAlias();
        $index  = $config->getIndexName();

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
