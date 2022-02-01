<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\FailedToImportObject;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\LoaderRecalculable;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Service as SearchService;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\SmartSave\BatchSave;
use App\Utils\Iterators\ObjectIterator;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Telescope\Telescope;
use Throwable;

use function min;

abstract class Importer {
    protected Loader   $loader;
    protected Resolver $resolver;
    protected ?Closure $onInit   = null;
    protected ?Closure $onChange = null;
    protected ?Closure $onFinish = null;

    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected Client $client,
        protected Container $container,
    ) {
        $this->onRegister();
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    protected function getContainer(): Container {
        return $this->container;
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
                /** @var \App\Services\DataLoader\Schema\Type&\App\Services\DataLoader\Schema\TypeWithId $item */
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

                    $this->getExceptionHandler()->report(
                        new FailedToImportObject($this, $item, $exception),
                    );
                } finally {
                    $status->processed++;
                }
            }

            $this->onAfterImport($status);
        });
    }

    private function call(Closure $closure): void {
        GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($closure): void {
            // Indexing should be disabled to avoid a lot of queued jobs and
            // speed up the import.

            SearchService::callWithoutIndexing(static function () use ($closure): void {
                // Telescope should be disabled because it stored all data in memory
                // and will dump it only after the job/command/request is finished.
                // For long-running jobs, this will lead to huge memory usage

                Telescope::withoutRecording(static function () use ($closure): void {
                    // Import creates a lot of objects, so would be good to
                    // group multiple inserts into one.

                    BatchSave::enable($closure);
                });
            });
        });
    }

    protected function getIterator(
        ?DateTimeInterface $from,
        ?int $chunk,
        ?int $limit,
        int|string|null $continue,
    ): ObjectIterator {
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
        $this->container->forgetInstances();

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

        // Reset container
        $this->container->forgetInstances();

        // Unset
        unset($this->resolver);
        unset($this->loader);

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

    abstract protected function makeIterator(DateTimeInterface $from = null): ObjectIterator;

    abstract protected function makeLoader(): Loader;

    abstract protected function makeResolver(): Resolver;

    abstract protected function getObjectsCount(DateTimeInterface $from = null): ?int;
}
