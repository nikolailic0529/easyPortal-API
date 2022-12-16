<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
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
