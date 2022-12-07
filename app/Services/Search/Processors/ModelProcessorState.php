<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Utils\Processor\EloquentState;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends EloquentState<TModel>
 */
class ModelProcessorState extends EloquentState {
    public bool $rebuild = false;
    public ?string $name = null;
}
