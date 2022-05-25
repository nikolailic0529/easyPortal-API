<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Exceptions\FailedToRecalculateModel;
use App\Services\Recalculator\Exceptions\RecalculateError;
use App\Utils\Eloquent\Events\Subject;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\State;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

/**
 * @template TItem of \Illuminate\Database\Eloquent\Model
 * @template TChunkData of \App\Services\Recalculator\Processor\ChunkData<TItem>
 * @template TState of \App\Utils\Processor\EloquentState<TItem>
 *
 * @extends EloquentProcessor<TItem, TChunkData, TState>
 */
abstract class Processor extends EloquentProcessor {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        private Subject $subject,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
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
    protected function chunkLoaded(State $state, array $items, mixed $data): void {
        // Configure
        $this->subject->onModelEvent($data);

        // Parent
        parent::chunkLoaded($state, $items, $data);
    }

    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return $data->isDirty()
            ? new ModelsRecalculated($state->model, $data->getKeys())
            : null;
    }
}
