<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Concerns\SmartSave\BatchInsert;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Service as SearchService;
use App\Services\Service;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Iterators\ObjectIteratorProperties;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Telescope\Telescope;
use LastDragon_ru\LaraASP\Core\Observer\Subject;
use Throwable;

use function count;

/**
 * @template TItem
 * @template TState of \App\Utils\Processor\State
 */
abstract class Processor {
    use ObjectIteratorProperties;

    private ?Service $service = null;
    private bool     $stopped = false;

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

    protected function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected Dispatcher $dispatcher,
    ) {
        $this->onInit   = new Subject();
        $this->onChange = new Subject();
        $this->onFinish = new Subject();
    }

    public function getService(): ?Service {
        return $this->service;
    }

    public function setService(?Service $service): static {
        $this->service = $service;

        return $this;
    }

    // <editor-fold desc="Process">
    // =========================================================================
    public function start(): void {
        $this->stopped = false;
        $state         = $this->getState() ?? $this->getDefaultState();

        try {
            $this->prepare($state);
        } catch (Interrupt) {
            // ok
        }
    }

    public function stop(): void {
        $this->stopped = true;
    }

    public function reset(): void {
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

                    BatchInsert::enable(function () use ($state): void {
                        $this->run($state);
                    });
                });
            });
        });
    }

    protected function run(State $state): void {
        $iterator = $this->getIterator()
            ->setLimit($this->getLimit())
            ->setOffset($state->offset)
            ->setChunkSize($this->getChunkSize())
            ->onBeforeChunk(function (array $items) use ($state): void {
                $this->chunkLoaded($state, $items);
            })
            ->onAfterChunk(function (array $items) use ($state, &$iterator): void {
                $this->chunkProcessed($state, $iterator->getOffset(), $items);
            });

        $this->init($state);

        foreach ($iterator as $item) {
            try {
                $this->process($item);

                $state->success++;
            } catch (Throwable $exception) {
                $state->failed++;

                $this->report($item, $exception);
            } finally {
                $state->processed++;
            }
        }

        $this->finish($state);
    }

    abstract protected function getTotal(): ?int;

    abstract protected function getIterator(): ObjectIterator;

    /**
     * @param TItem $item
     */
    abstract protected function process(mixed $item): void;

    /**
     * @param TItem $item
     */
    protected function report(mixed $item, Throwable $exception): void {
        throw $exception;
    }
    //</editor-fold>

    // <editor-fold desc="Events">
    // =========================================================================
    public function onInit(?Closure $closure): static {
        if ($closure) {
            $this->onInit->attach($closure);
        } else {
            $this->onInit->reset();
        }

        return $this;
    }

    public function onChange(?Closure $closure): static {
        if ($closure) {
            $this->onChange->attach($closure);
        } else {
            $this->onChange->reset();
        }

        return $this;
    }

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
    protected function init(State $state): void {
        $this->onInit->notify(clone $state);
    }

    /**
     * @param TState $state
     */
    protected function finish(State $state): void {
        $this->onFinish->notify($state);
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
    protected function chunkProcessed(State $state, string|int|null $offset, array $items): void {
        // Update state
        $previous         = clone $state;
        $state->chunk     = $state->chunk + 1;
        $state->offset    = $offset;
        $state->processed = $state->processed + count($items);

        $this->saveState($state);

        // Notify
        $this->onChange->notify(clone $state);

        // Event
        $event = $this->getOnChangeEvent($previous, $items);

        if ($event) {
            $this->dispatcher->dispatch($event);
        }

        // Stopped?
        if ($this->stopped) {
            throw new Interrupt();
        }
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     */
    abstract protected function getOnChangeEvent(State $state, array $items): ?object;
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @return TState|null
     */
    protected function getState(): ?State {
        return $this->service?->get($this, fn(array $state) => $this->restoreState($state));
    }

    /**
     * @return TState
     */
    protected function getDefaultState(): State {
        return new State([
            'offset' => $this->getOffset(),
            'total'  => $this->getTotal(),
        ]);
    }

    /**
     * @param TState $state
     */
    protected function saveState(State $state): void {
        $this->service?->set($this, $state);
    }

    protected function resetState(): void {
        $this->service?->delete($this);
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
