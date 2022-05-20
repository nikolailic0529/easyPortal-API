<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Services\Search\Eloquent\Searchable;
use App\Utils\Processor\CompositeState;
use Illuminate\Database\Eloquent\Model;

class ModelsProcessorState extends CompositeState {
    /**
     * @var array<int, class-string<Model&Searchable>>
     */
    public array $models;
}
