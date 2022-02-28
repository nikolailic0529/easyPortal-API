<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Service as SearchService;
use App\Services\Service;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\SmartSave\BatchSave;
use App\Utils\Iterators\Concerns\ChunkSize;
use App\Utils\Iterators\Concerns\Limit;
use App\Utils\Iterators\Concerns\Offset;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Laravel\Telescope\Telescope;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use LogicException;
use Throwable;

use function min;

/**
 * The Processor is specially designed to process a huge amount of items and
 * provides a way to concentrate on implementation without worries about all
 * other stuff.
 *
 * Behind the scenes, it uses `ObjectIterator` to get items divided into chunks,
 * calls events for each chunk (that, for example, allows us to implement Eager
 * Loading and alleviate the "N + 1" problem), and finally calls `process()`
 * to process the item.
 *
 * In addition, it can also save (and restore of course) the internal state
 * after each chunk that is especially useful for queued jobs to continue
 * processing after error/timeout/stop signal/etc.
 *
 * @see \App\Utils\Iterators\Contracts\ObjectIterator
 * @see \App\Services\Queue\Concerns\ProcessorJob
 *
 * @template TItem
 * @template TChunkData
 * @template TState of \App\Utils\Processor\State
 */
abstract class Processor {
    use Limit;
    use Offset;
    use ChunkSize;

    private mixed    $cacheKey = null;
    private ?Service $service  = null;
    private bool     $stopped  = false;
    private bool     $running  = false;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Dispatcher<TState>
     */
    private Dispatcher $onInit;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Dispatcher<TState>
     */
    private Dispatcher $onChange;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Dispatcher<TState>
     */
    private Dispatcher $onFinish;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Dispatcher<TState>
     */
    private Dispatcher $onProcess;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Dispatcher<TState>
     */
    private Dispatcher $onReport;

    public function __construct(
        private ExceptionHandler $exceptionHandler,
        private EventDispatcher $dispatcher,
    ) {
        $this->onInit    = new Dispatcher();
        $this->onChange  = new Dispatcher();
        $this->onFinish  = new Dispatcher();
        $this->onReport  = new Dispatcher();
        $this->onProcess = new Dispatcher();
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    protected function getDispatcher(): EventDispatcher {
        return $this->dispatcher;
    }

    public function isStopped(): bool {
        return $this->stopped;
    }

    public function isRunning(): bool {
        return $this->running;
    }

    protected function getService(): ?Service {
        return $this->service;
    }

    protected function getCacheKey(): mixed {
        return $this->cacheKey;
    }

    public function setCacheKey(?Service $service, mixed $key): static {
        $this->service  = $service;
        $this->cacheKey = $key;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Process">
    // =========================================================================
    public function start(): bool {
        $this->running = true;
        $this->stopped = false;
        $state         = $this->getState() ?? $this->getDefaultState();

        try {
            $this->prepare($state);
        } catch (Interrupt) {
            // ok
        } finally {
            $this->running = false;
        }

        return $state->failed === 0;
    }

    public function stop(): void {
        $this->stopped = true;
    }

    public function reset(): void {
        if ($this->running) {
            throw new LogicException('Reset is not possible while running.');
        }

        $this->resetState();
    }

    /**
     * @param TState $state
     */
    protected function prepare(State $state): void {
        $this->call(function () use ($state): void {
            $this->run($state);
        });
    }

    /**
     * @param TState $state
     */
    protected function run(State $state): void {
        $data     = null;
        $sync     = static function () use (&$iterator, $state): void {
            $state->index  = $iterator->getIndex();
            $state->offset = $iterator->getOffset();
        };
        $iterator = $this->getIterator($state)
            ->setIndex($state->index)
            ->setLimit($state->limit)
            ->setOffset($state->offset)
            ->setChunkSize($this->getChunkSize())
            ->onInit($sync)
            ->onFinish($sync)
            ->onBeforeChunk(function (array $items) use ($state, &$data): void {
                $data = $this->prefetch($state, $items);

                $this->chunkLoaded($state, $items, $data);
            })
            ->onAfterChunk(function (array $items) use ($sync, $state, &$data): void {
                $sync();

                $this->chunkProcessed($state, $items, $data);

                $data = null;
            });

        $this->init($state);

        foreach ($iterator as $item) {
            try {
                $this->process($state, $data, $item);

                $state->success++;

                $this->notifyOnProcess($state);
            } catch (Throwable $exception) {
                $state->failed++;

                $this->report($exception, $item);
                $this->notifyOnReport($state);
            } finally {
                $state->processed++;

                $sync();
            }
        }

        $this->finish($state);
    }

    /**
     * @param TState $state
     */
    abstract protected function getTotal(State $state): ?int;

    /**
     * @param TState $state
     *
     * @return \App\Utils\Iterators\Contracts\ObjectIterator<TItem>
     */
    abstract protected function getIterator(State $state): ObjectIterator;

    /**
     * @param TState          $state
     * @param TChunkData|null $data
     * @param TItem           $item
     */
    abstract protected function process(State $state, mixed $data, mixed $item): void;

    /**
     * @param TItem|null $item
     */
    abstract protected function report(Throwable $exception, mixed $item = null): void;

    /**
     * @param TState       $state
     * @param array<TItem> $items
     *
     * @return TChunkData|null
     */
    abstract protected function prefetch(State $state, array $items): mixed;
    //</editor-fold>

    // <editor-fold desc="Events">
    // =========================================================================
    /**
     * @param \Closure(TState)|null $closure
     *
     * @return $this<TItem, TChunkData, TState>
     */
    public function onInit(?Closure $closure): static {
        if ($closure) {
            $this->onInit->attach($closure);
        } else {
            $this->onInit->reset();
        }

        return $this;
    }

    /**
     * @param TState $state
     */
    protected function notifyOnInit(State $state): void {
        $this->onInit->notify(clone $state);
    }

    /**
     * @param \Closure(TState, array<TItem>)|null $closure
     *
     * @return $this<TItem, TChunkData, TState>
     */
    public function onChange(?Closure $closure): static {
        if ($closure) {
            $this->onChange->attach($closure);
        } else {
            $this->onChange->reset();
        }

        return $this;
    }

    /**
     * @param TState $state
     */
    protected function notifyOnChange(State $state): void {
        $this->onChange->notify(clone $state);
    }

    /**
     * @param \Closure(TState)|null $closure
     *
     * @return $this<TItem, TChunkData, TState>
     */
    public function onFinish(?Closure $closure): static {
        if ($closure) {
            $this->onFinish->attach($closure);
        } else {
            $this->onFinish->reset();
        }

        return $this;
    }

    /**
     * @param TState $state
     */
    protected function notifyOnFinish(State $state): void {
        $this->onFinish->notify(clone $state);
    }

    /**
     * @param \Closure(TState, array<TItem>)|null $closure
     *
     * @return $this<TItem, TChunkData, TState>
     */
    public function onProcess(?Closure $closure): static {
        if ($closure) {
            $this->onProcess->attach($closure);
        } else {
            $this->onProcess->reset();
        }

        return $this;
    }

    /**
     * @param TState $state
     */
    protected function notifyOnProcess(State $state): void {
        $this->onProcess->notify(clone $state);
    }

    /**
     * @param \Closure(TState, array<TItem>)|null $closure
     *
     * @return $this<TItem, TChunkData, TState>
     */
    public function onReport(?Closure $closure): static {
        if ($closure) {
            $this->onReport->attach($closure);
        } else {
            $this->onReport->reset();
        }

        return $this;
    }

    /**
     * @param TState $state
     */
    protected function notifyOnReport(State $state): void {
        $this->onReport->notify(clone $state);
    }

    /**
     * @param TState $state
     */
    protected function init(State $state): void {
        $this->saveState($state);
        $this->notifyOnInit($state);
    }

    /**
     * @param TState $state
     */
    protected function finish(State $state): void {
        $this->notifyOnFinish($state);
        $this->resetState();
    }

    /**
     * @param TState          $state
     * @param array<TItem>    $items
     * @param TChunkData|null $data
     */
    protected function chunkLoaded(State $state, array $items, mixed $data): void {
        // empty
    }

    /**
     * @param TState          $state
     * @param array<TItem>    $items
     * @param TChunkData|null $data
     */
    protected function chunkProcessed(State $state, array $items, mixed $data): void {
        // Update state
        $this->saveState($state);

        // Notify
        $this->notifyOnChange($state);
        $this->dispatchOnChange($state, $items, $data);

        // Stopped?
        if ($this->stopped) {
            throw new Interrupt();
        }
    }

    /**
     * @param TState          $state
     * @param array<TItem>    $items
     * @param TChunkData|null $data
     */
    protected function dispatchOnChange(State $state, array $items, mixed $data): void {
        $event = $this->getOnChangeEvent($state, $items, $data);

        if ($event) {
            $this->getDispatcher()->dispatch($event);
        }
    }

    /**
     * @param TState          $state
     * @param array<TItem>    $items
     * @param TChunkData|null $data
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return null;
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @return TState|null
     */
    public function getState(): ?State {
        return $this->getService()?->get($this->getCacheKey(), function (array $state): ?State {
            try {
                return $this->restoreState($state);
            } catch (Throwable $exception) {
                $this->resetState();
                $this->report($exception);
            }

            return null;
        });
    }

    /**
     * @return TState
     */
    protected function getDefaultState(): State {
        // State
        $limit  = $this->getLimit();
        $offset = $this->getOffset();
        $state  = $this->restoreState($this->defaultState([
            'index'  => 0,
            'limit'  => $limit,
            'offset' => $offset,
        ]));

        // Total
        $total        = $this->getTotal($state);
        $state->total = $total;

        if ($limit !== null && $total !== null) {
            $state->total = min($limit, $total);
        } else {
            $state->total ??= $limit;
        }

        // Return
        return $state;
    }

    /**
     * @param TState $state
     */
    protected function saveState(State $state): void {
        $this->getService()?->set($this->getCacheKey(), $state);
    }

    protected function resetState(): void {
        $this->getService()?->delete($this->getCacheKey());
    }

    /**
     * @param array<mixed> $state
     *
     * @return array<mixed>
     */
    protected function defaultState(array $state): array {
        return $state;
    }

    /**
     * @param array<mixed> $state
     *
     * @return TState
     */
    protected function restoreState(array $state): State {
        return State::make($state);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @template T
     *
     * @param \Closure(): T $callback
     *
     * @return T
     */
    protected function call(Closure $callback): mixed {
        $result = null;

        // Organization scope should be disabled because we want to process
        // all objects.
        GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use (&$result, $callback): void {
                // Indexing should be disabled to avoid a lot of queued jobs and
                // speed up processing.

                SearchService::callWithoutIndexing(static function () use (&$result, $callback): void {
                    // Telescope should be disabled because it stored all data in memory
                    // and will dump it only after the job/command/request is finished.
                    // For long-running jobs, this will lead to huge memory usage

                    Telescope::withoutRecording(static function () use (&$result, $callback): void {
                        // Processor can create a lot of objects, so will be good to
                        // group multiple inserts into one.

                        BatchSave::enable(static function () use (&$result, $callback): void {
                            $result = $callback();
                        });
                    });
                });
            },
        );

        return $result;
    }
    // </editor-fold>
}
