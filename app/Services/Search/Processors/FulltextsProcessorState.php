<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Utils\Processor\CompositeState;
use Illuminate\Database\Eloquent\Model;

class FulltextsProcessorState extends CompositeState {
    /**
     * @var array<int, class-string<Model>>
     */
    public array $models;
}
