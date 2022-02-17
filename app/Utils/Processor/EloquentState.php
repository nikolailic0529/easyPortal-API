<?php declare(strict_types = 1);

namespace App\Utils\Processor;

class EloquentState extends State {
    /**
     * @var array<string>
     */
    public array $keys        = [];
    public bool  $withTrashed = false;
}
