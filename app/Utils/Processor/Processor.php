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
use App\Utils\Iterators\ObjectIterator;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use Laravel\Telescope\Telescope;
use LastDragon_ru\LaraASP\Core\Observer\Subject;
use LogicException;
use Throwable;

use function min;

/**
 * @template TItem
 * @template TChunkData
 * @template TState of \App\Utils\Processor\State
 */
abstract class Processor {
    use Limit;
    use Offset;
    use ChunkSize;

    private bool $stopped = false;
    private bool $running = false;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Subject<TState>
     */
    private Subject $onInit;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Subject<TState>
     */
    private Subject $onChange;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Subject<TState>
     */
    private Subject $onFinish;

    public function __construct(
        private ExceptionHandler $exceptionHandler,
        private Dispatcher $dispatcher,
        private ?Service $service,
    ) {
        $this->onInit   = new Subject();
        $this->onChange = new Subject();
        $this->onFinish = new Subject();
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    protected function getDispatcher(): Dispatcher {
        return $this->dispatcher;
    }

    public function getService(): ?Service {
        return $this->service;
    }

    public function isStopped(): bool {
        return $this->stopped;
    }

    public function isRunning(): bool {
        return $this->running;
    }
    // </editor-fold>

    // <editor-fold desc="Process">
    // =========================================================================
    public function start(): void {
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
        // Organization scope should be disabled because we want to process
        // all objects.
        GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($state): void {
            // Indexing should be disabled to avoid a lot of queued jobs and
            // speed up processing.

            SearchService::callWithoutIndexing(function () use ($state): void {
                // Telescope should be disabled because it stored all data in memory
                // and will dump it only after the job/command/request is finished.
                // For long-running jobs, this will lead to huge memory usage

                Telescope::withoutRecording(function () use ($state): void {
                    // Processor can create a lot of objects, so will be good to
                    // group multiple inserts into one.

                    BatchSave::enable(function () use ($state): void {
                        $this->run($state);
                    });
                });
            });
        });
    }

    protected function run(State $state): void {
        $data     = null;
        $iterator = $this->getIterator()
            ->setIndex($state->index)
            ->setLimit($state->limit)
            ->setOffset($state->offset)
            ->setChunkSize($this->getChunkSize())
            ->onBeforeChunk(function (array $items) use ($state, &$data): void {
                $data = $this->prefetch($state, $items);

                $this->chunkLoaded($state, $items);
            })
            ->onAfterChunk(function (array $items) use ($state, &$data): void {
                $data = null;

                $this->chunkProcessed($state, $items);
            });

        $this->init($state);

        foreach ($iterator as $item) {
            try {
                $this->process($data, $item);

                $state->success++;
            } catch (Throwable $exception) {
                $state->failed++;

                $this->report($exception, $item);
            } finally {
                $state->index  = $iterator->getIndex();
                $state->offset = $iterator->getOffset();
                $state->processed++;
            }
        }

        $this->finish($state);
    }

    abstract protected function getTotal(): ?int;

    abstract protected function getIterator(): ObjectIterator;

    /**
     * @param TChunkData|null $data
     * @param TItem           $item
     */
    abstract protected function process(mixed $data, mixed $item): void;

    /**
     * @param TItem $item
     */
    abstract protected function report(Throwable $exception, mixed $item): void;

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
     * @return $this<TItem, TState>
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
     * @return $this<TItem, TState>
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
     * @return $this<TItem, TState>
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
     * @param TState $state
     */
    protected function init(State $state): void {
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
     * @param TState       $state
     * @param array<TItem> $items
     */
    protected function chunkLoaded(State $state, array $items): void {
        // empty
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     */
    protected function chunkProcessed(State $state, array $items): void {
        // Update state
        $this->saveState($state);

        // Notify
        $this->notifyOnChange($state);
        $this->dispatchOnChange($state, $items);

        // Stopped?
        if ($this->stopped) {
            throw new Interrupt();
        }
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     */
    protected function dispatchOnChange(State $state, array $items): void {
        $event = $this->getOnChangeEvent($state, $items);

        if ($event) {
            $this->getDispatcher()->dispatch($event);
        }
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     */
    protected function getOnChangeEvent(State $state, array $items): ?object {
        return null;
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @return TState|null
     */
    public function getState(): ?State {
        return $this->getService()?->get($this, fn(array $state) => $this->restoreState($state));
    }

    /**
     * @return TState
     */
    protected function getDefaultState(): State {
        $limit = $this->getLimit();
        $total = $this->getTotal();

        if ($limit !== null && $total !== null) {
            $total = min($limit, $total);
        } else {
            $total ??= $limit;
        }

        return new State([
            'index'   => 0,
            'limit'   => $limit,
            'total'   => $total,
            'offset'  => $this->getOffset(),
            'started' => Date::now(),
        ]);
    }

    /**
     * @param TState $state
     */
    protected function saveState(State $state): void {
        $this->getService()?->set($this, $state);
    }

    protected function resetState(): void {
        $this->getService()?->delete($this);
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
}
