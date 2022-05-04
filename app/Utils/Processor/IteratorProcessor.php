<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Service as SearchService;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\SmartSave\BatchSave;
use Closure;
use Laravel\Telescope\Telescope;

/**
 * The Processor for Iterator.
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
            parent::invoke($state);
        });
    }
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
