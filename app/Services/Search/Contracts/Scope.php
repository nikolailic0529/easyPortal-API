<?php declare(strict_types = 1);

namespace App\Services\Search\Contracts;

use App\Services\Search\Builders\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface Scope {
    /**
     * @param Builder<TModel> $builder
     * @param TModel          $model
     */
    public function applyForSearch(Builder $builder, Model $model): void;
}
