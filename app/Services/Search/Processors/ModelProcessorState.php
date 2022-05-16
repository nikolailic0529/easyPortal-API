<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Utils\Processor\EloquentState;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends EloquentState<TModel>
 */
class ModelProcessorState extends EloquentState {
    public bool $rebuild = false;
    public ?string $name = null;
}
