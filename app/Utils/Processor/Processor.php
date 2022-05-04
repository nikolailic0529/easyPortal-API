<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Concerns\ChunkSize;
use App\Utils\Iterators\Concerns\Limit;
use App\Utils\Iterators\Concerns\Offset;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\Contracts\Processor as ProcessorContract;
use App\Utils\Processor\Contracts\StateStore;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
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
 * Please note that while processing only `State` properties should be used. So
 * if you need to pass some settings into {@see Processor::process()} you need
 * extend the {@see \App\Utils\Processor\State} class with
 * {@see Processor::restoreState()} and {@see Processor::defaultState()}.
 *
 * @internal Please extend one of subclasses instead.
 *
 * @see \App\Utils\Iterators\Contracts\ObjectIterator
 * @see \App\Services\Queue\Concerns\ProcessorJob
 *
 * @template TItem
 * @template TChunkData
 * @template TState of State
 *
 * @implements ProcessorContract<TItem, TChunkData, TState>
 */
abstract class Processor implements ProcessorContract {
    use Limit;
    use Offset;
    use ChunkSize;

    private ?StateStore $store   = null;
    private bool        $stopped = false;
    private bool        $running = false;

    /**
     * @var Dispatcher<TState>
     */
    private Dispatcher $onInit;

    /**
     * @var Dispatcher<TState>
     */
    private Dispatcher $onChange;

    /**
     * @var Dispatcher<TState>
     */
    private Dispatcher $onFinish;

    /**
     * @var Dispatcher<TState>
     */
    private Dispatcher $onProcess;

    /**
     * @var Dispatcher<TState>
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

    public function getStore(): ?StateStore {
        return $this->store;
    }

    public function setStore(?StateStore $store): static {
        $this->store = $store;

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
            $this->invoke($state);
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
     * @param TItem  $item
     */
    protected function success(mixed $state, mixed $item): void {
        $state->processed++;
        $state->success++;

        $this->notifyOnProcess($state);
        $this->dispatchOnProcess($item);
    }

    /**
     * @param TState $state
     * @param TItem  $item
     */
    protected function failed(mixed $state, mixed $item, Throwable $exception): void {
        $state->processed++;
        $state->failed++;

        $this->report($exception, $item);
        $this->notifyOnReport($state);
    }

    /**
     * @param TState $state
     */
    protected function invoke(State $state): void {
        $data     = null;
        $sync     = static function (ObjectIterator $iterator) use ($state): void {
            $state->index  = $iterator->getIndex();
            $state->offset = $iterator->getOffset();
        };
        $iterator = $this->getIterator($state);
        $iterator = $iterator
            ->setIndex($state->index)
            ->setLimit($state->limit)
            ->setOffset($state->offset)
            ->setChunkSize($this->getChunkSize())
            ->onInit(static function () use ($iterator, $sync): void {
                $sync($iterator);
            })
            ->onFinish(static function () use ($iterator, $sync): void {
                $sync($iterator);
            })
            ->onBeforeChunk(function (array $items) use ($state, &$data): void {
                $data = $this->prefetch($state, $items);

                $this->chunkLoaded($state, $items, $data);
            })
            ->onAfterChunk(function (array $items) use ($iterator, $sync, $state, &$data): void {
                $sync($iterator);

                $this->chunkProcessed($state, $items, $data);

                $data = null;
            });

        $this->init($state);

        foreach ($iterator as $item) {
            // Iterator updates index after element
            $sync($iterator);

            // Process
            try {
                $this->process($state, $data, $item);
                $this->success($state, $item);
            } catch (Throwable $exception) {
                $this->failed($state, $item, $exception);
            }
        }

        $this->finish($state);
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     *
     * @return TChunkData
     */
    abstract protected function prefetch(State $state, array $items): mixed;

    /**
     * @param TState     $state
     * @param TChunkData $data
     * @param TItem      $item
     */
    abstract protected function process(State $state, mixed $data, mixed $item): void;

    /**
     * @param TItem|null $item
     */
    abstract protected function report(Throwable $exception, mixed $item = null): void;

    /**
     * @param TState $state
     */
    abstract protected function getTotal(State $state): ?int;

    /**
     * @param TState $state
     *
     * @return ObjectIterator<TItem>
     */
    abstract protected function getIterator(State $state): ObjectIterator;
    // </editor-fold>

    // <editor-fold desc="Events">
    // =========================================================================
    /**
     * @param Closure(TState): void|null $closure
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
     * @param Closure(TState): void|null $closure
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
     * @param Closure(TState): void|null $closure
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
     * @param Closure(TState): void|null $closure
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
     * @param Closure(TState): void|null $closure
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
     * @param TState       $state
     * @param array<TItem> $items
     * @param TChunkData   $data
     */
    protected function chunkLoaded(State $state, array $items, mixed $data): void {
        // empty
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     * @param TChunkData   $data
     */
    protected function chunkProcessed(State $state, array $items, mixed $data): void {
        // Update state
        $this->saveState($state);

        // Notify
        $this->notifyOnChange($state);
        $this->dispatchOnChange($state, $items, $data);

        // Stopped?
        if ($this->isStopped()) {
            throw new Interrupt();
        }
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     * @param TChunkData   $data
     */
    protected function dispatchOnChange(State $state, array $items, mixed $data): void {
        $event = $this->getOnChangeEvent($state, $items, $data);

        if ($event) {
            $this->getDispatcher()->dispatch($event);
        }
    }

    /**
     * @param TState       $state
     * @param array<TItem> $items
     * @param TChunkData   $data
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return null;
    }

    /**
     * @param TItem $item
     */
    protected function dispatchOnProcess(mixed $item): void {
        $event = $this->getOnProcessEvent($item);

        if ($event) {
            $this->getDispatcher()->dispatch($event);
        }
    }

    /**
     * @param TItem $item
     */
    protected function getOnProcessEvent(mixed $item): ?object {
        return null;
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @return TState|null
     */
    public function getState(): ?State {
        try {
            $state = $this->getStore()?->get();
            $state = $state !== null
                ? $this->restoreState($state)
                : null;

            return $state;
        } catch (Throwable $exception) {
            $this->resetState();
            $this->report($exception);
        }

        return null;
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
        $this->getStore()?->save($state);
    }

    protected function resetState(): void {
        $this->getStore()?->delete();
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    protected function defaultState(array $state): array {
        return $state;
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return TState
     */
    protected function restoreState(array $state): State {
        return new State($state);
    }
    // </editor-fold>
}
