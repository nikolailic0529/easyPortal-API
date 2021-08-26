<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\LoaderRecalculable;
use App\Services\DataLoader\Resolver;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Service as SearchService;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Laravel\Telescope\Telescope;
use Psr\Log\LoggerInterface;
use Throwable;

use function min;

abstract class Importer {
    use GlobalScopes;

    protected ContainerContract $container;
    protected Loader            $loader;
    protected Resolver          $resolver;
    protected ?Closure          $onInit   = null;
    protected ?Closure          $onChange = null;
    protected ?Closure          $onFinish = null;

    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
        private ContainerContract $root,
    ) {
        // empty
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

    public function import(
        bool $update = false,
        DateTimeInterface $from = null,
        string|int $continue = null,
        int $chunk = null,
        int $limit = null,
    ): void {
        $this->call(function () use ($update, $from, $continue, $chunk, $limit): void {
            $models   = [];
            $status   = new Status($from, $continue, $this->getTotal($from, $limit));
            $iterator = $this
                ->getIterator($from, $chunk, $limit, $continue)
                ->onBeforeChunk(function (array $items) use ($status): void {
                    $this->onBeforeChunk($items, $status);
                })
                ->onAfterChunk(function () use (&$iterator, &$models, $status): void {
                    $this->onAfterChunk($models, $status, $iterator->getOffset());

                    $models = [];
                });

            $this->onBeforeImport($status);

            foreach ($iterator as $item) {
                /** @var \App\Services\DataLoader\Schema\Type|\App\Services\DataLoader\Schema\TypeWithId $item */
                try {
                    $model = null;

                    if ($this->resolver->get($item->id)) {
                        if ($update) {
                            $model = $this->loader->update($item);
                            $status->updated++;
                        } else {
                            $status->ignored++;
                        }
                    } else {
                        $model = $this->loader->create($item);
                        $status->created++;
                    }

                    if ($model) {
                        $models[] = $model;
                    }
                } catch (Throwable $exception) {
                    $status->failed++;

                    $this->logger->warning('Failed to import object.', [
                        'importer'  => $this::class,
                        'object'    => $item,
                        'exception' => $exception,
                    ]);
                } finally {
                    $status->processed++;
                }
            }

            $this->onAfterImport($status);
        });
    }

    private function call(Closure $closure): void {
        $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($closure): void {
            // Indexing should be disabled to avoid a lot of queued jobs and
            // speed up the import.

            SearchService::callWithoutIndexing(static function () use ($closure): void {
                // Telescope should be disabled because it stored all data in memory
                // and will dump it only after the job/command/request is finished.
                // For long-running jobs, this will lead to huge memory usage

                Telescope::withoutRecording($closure);
            });
        });
    }

    protected function getIterator(
        ?DateTimeInterface $from,
        ?int $chunk,
        ?int $limit,
        int|string|null $continue,
    ): QueryIterator {
        $iterator = $this->makeIterator($from);

        if ($chunk) {
            $iterator->setChunkSize($chunk);
        }

        if ($limit) {
            $iterator->setLimit($limit);
        }

        if ($continue) {
            $iterator->setOffset($continue);
        }

        return $iterator;
    }

    protected function getTotal(DateTimeInterface $from = null, int $limit = null): ?int {
        $total   = null;
        $objects = $this->getObjectsCount($from);

        if ($objects !== null && $limit !== null) {
            $total = min($limit, $objects);
        } elseif ($objects !== null) {
            $total = $objects;
        } elseif ($limit !== null) {
            $total = $limit;
        } else {
            // empty
        }

        return $total;
    }

    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Reset container
        $this->container = $this->root->make(Container::class);

        $this->onRegister();

        // Reset objects
        $this->resolver = $this->makeResolver();
        $this->loader   = $this->makeLoader();

        // Configure
        if ($this->loader instanceof LoaderRecalculable) {
            $this->loader->setRecalculate(false);
        }
    }

    /**
     * @param array<\Illuminate\Database\Eloquent\Model> $models
     */
    protected function onAfterChunk(array $models, Status $status, string|int|null $continue): void {
        // Update status
        $status->continue = $continue;
        $status->chunk++;

        // Update calculated properties
        if ($this->loader instanceof LoaderRecalculable && ($status->created || $status->updated || $status->failed)) {
            $this->loader->recalculate(true);
        }

        // Call callback
        if ($this->onChange) {
            ($this->onChange)($models, clone $status);
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

    protected function onRegister(): void {
        // empty
    }

    abstract protected function makeIterator(DateTimeInterface $from = null): QueryIterator;

    abstract protected function makeLoader(): Loader;

    abstract protected function makeResolver(): Resolver;

    abstract protected function getObjectsCount(DateTimeInterface $from = null): ?int;
}
