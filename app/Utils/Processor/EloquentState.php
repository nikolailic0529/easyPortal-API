<?php declare(strict_types = 1);

namespace App\Utils\Processor;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class EloquentState extends State {
    /**
     * @var class-string<TModel>
     */
    public string $model;

    /**
     * @var array<string>|null
     */
    public ?array $keys        = null;
    public bool   $withTrashed = false;
}
