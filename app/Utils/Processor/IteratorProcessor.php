<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Service as SearchService;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\SmartSave\BatchSave;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Laravel\Telescope\Telescope;
use Throwable;

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
 * if you need to pass some settings into {@see IteratorProcessor::process()}
 * you need extend the {@see \App\Utils\Processor\State} class with
 * {@see IteratorProcessor::restoreState()} and {@see IteratorProcessor::defaultState()}.
 *
 * @see \App\Utils\Iterators\Contracts\ObjectIterator
 * @see \App\Services\Queue\Concerns\ProcessorJob
 *
 * @template TItem
 * @template TChunkData
 * @template TState of State
 *
 * @extends Processor<TItem, TChunkData, TState>
 */
abstract class IteratorProcessor extends Processor {
    // <editor-fold desc="Process">
    // =========================================================================
    /**
     * @param TState $state
     */
    protected function invoke(State $state): void {
        $this->call(function () use ($state): void {
            $this->run($state);
        });
    }

    /**
     * @param TState $state
     */
    protected function run(State $state): void {
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
            try {
                $this->process($state, $data, $item);
                $this->success($state, $item);
            } catch (Throwable $exception) {
                $this->failed($state, $item, $exception);
            } finally {
                $sync($iterator);
            }
        }

        $this->finish($state);
    }

    /**
     * @param TState $state
     *
     * @return ObjectIterator<TItem>
     */
    abstract protected function getIterator(State $state): ObjectIterator;

    /**
     * @param TState     $state
     * @param TChunkData $data
     * @param TItem      $item
     */
    abstract protected function process(State $state, mixed $data, mixed $item): void;

    /**
     * @param TState       $state
     * @param array<TItem> $items
     *
     * @return TChunkData
     */
    abstract protected function prefetch(State $state, array $items): mixed;
    //</editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T|null
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
