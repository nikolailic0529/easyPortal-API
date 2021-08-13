<?php declare(strict_types = 1);

namespace App\Models\Concerns\GlobalScopes;

use App\Services\Search\Builders\Builder as SearchBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

abstract class DisableableScope implements Scope {
    // <editor-fold desc="Eloquent">
    // =========================================================================
    final public function apply(EloquentBuilder $builder, Model $model): void {
        if ($this->isEnabled()) {
            $this->handle($builder, $model);
        }
    }

    abstract protected function handle(EloquentBuilder $builder, Model $model): void;
    //</editor-fold>

    // <editor-fold desc="Search">
    // =========================================================================
    final public function applyForSearch(SearchBuilder $builder, Model $model): void {
        if ($this->isEnabled()) {
            $this->handleForSearch($builder, $model);
        }
    }

    abstract protected function handleForSearch(SearchBuilder $builder, Model $model): void;
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function isEnabled(): bool {
        return State::isEnabled($this::class);
    }
    // </editor-fold>
}
