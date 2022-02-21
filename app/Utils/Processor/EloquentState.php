<?php declare(strict_types = 1);

namespace App\Utils\Processor;

class EloquentState extends State {
    /**
     * @var array<string>|null
     */
    public ?array $keys        = null;
    public bool   $withTrashed = false;
}
