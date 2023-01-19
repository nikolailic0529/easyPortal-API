<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\GlobalScopes;

use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @template TModel of Model
 */
abstract class DisableableScope implements Scope {
    // <editor-fold desc="Eloquent">
    // =========================================================================
    /**
     * @param EloquentBuilder<TModel> $builder
     * @param TModel                  $model
     */
    final public function apply(EloquentBuilder $builder, Model $model): void {
        if ($this->isEnabled()) {
            $this->applyForce($builder, $model);
        }
    }

    /**
     * @param EloquentBuilder<TModel> $builder
     * @param TModel                  $model
     */
    final public function applyForce(EloquentBuilder $builder, Model $model): void {
        $this->handle($builder, $model);
    }

    /**
     * @param EloquentBuilder<TModel> $builder
     * @param TModel                  $model
     */
    abstract protected function handle(EloquentBuilder $builder, Model $model): void;
    //</editor-fold>

    // <editor-fold desc="Search">
    // =========================================================================
    /**
     * @param SearchBuilder<TModel> $builder
     * @param TModel                $model
     */
    final public function applyForSearch(SearchBuilder $builder, Model $model): void {
        if ($this->isEnabled()) {
            $this->handleForSearch($builder, $model);
        }
    }

    /**
     * @param SearchBuilder<TModel> $builder
     * @param TModel                $model
     */
    abstract protected function handleForSearch(SearchBuilder $builder, Model $model): void;
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function isEnabled(): bool {
        return State::isEnabled($this::class);
    }
    // </editor-fold>
}
