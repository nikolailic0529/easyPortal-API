<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Exceptions\FailedToRecalculateModel;
use App\Services\Recalculator\Exceptions\RecalculateError;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\State;
use Throwable;

/**
 * @template TItem of \Illuminate\Database\Eloquent\Model
 * @template TChunkData of \App\Services\Recalculator\Processor\ChunkData
 * @template TState of \App\Utils\Processor\EloquentState<TItem>
 *
 * @extends EloquentProcessor<TItem, TChunkData, TState>
 */
abstract class Processor extends EloquentProcessor {
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
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return new ModelsRecalculated($state->model, $data->getKeys());
    }
}
