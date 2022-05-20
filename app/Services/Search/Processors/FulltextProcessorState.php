<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Model;

class FulltextProcessorState extends State {
    /**
     * @var array<class-string<Model>>
     */
    public array $models;
}
