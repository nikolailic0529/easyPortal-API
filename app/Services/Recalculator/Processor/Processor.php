<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Exceptions\FailedToRecalculateModel;
use App\Services\Recalculator\Exceptions\RecalculateError;
use App\Utils\Eloquent\Events\Subject;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\State;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @template TItem of Model
 * @template TChunkData of ChunkData<TItem>
 * @template TState of EloquentState<TItem>
 *
 * @extends EloquentProcessor<TItem, TChunkData, TState>
 */
abstract class Processor extends EloquentProcessor {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        private Subject $subject,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function isWithTrashed(): bool {
        return true;
    }
    // </editor-fold>

    // <editor-fold desc="Process">
    // =========================================================================
    protected function item(State $state, mixed $data, mixed $item): void {
        $data->setModel($item);

        try {
            parent::item($state, $data, $item);
        } finally {
            $data->setModel(null);
        }
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        $this->getExceptionHandler()->report(
            $item
                ? new FailedToRecalculateModel($this, $item, $exception)
                : new RecalculateError($this, $exception),
        );
    }

    /**
     * @inheritDoc
     */
    protected function chunkLoaded(State $state, array $items): mixed {
        $data = parent::chunkLoaded($state, $items);

        $this->subject->onModelEvent($data);

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return $data->isDirty()
            ? new ModelsRecalculated($state->model, $data->getDirtyKeys())
            : null;
    }
    // </editor-fold>

    // <editor-fold desc="ChunkSize">
    // =========================================================================
    public function getDefaultChunkSize(): int {
        return 100;
    }
    // </editor-fold>
}
