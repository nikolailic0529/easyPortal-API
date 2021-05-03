<?php declare(strict_types = 1);

namespace App\Models\Concerns\GlobalScopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

abstract class DisableableScope implements Scope {
    final public function apply(Builder $builder, Model $model): void {
        if (State::isEnabled($this::class)) {
            $this->handle($builder, $model);
        }
    }

    abstract protected function handle(Builder $builder, Model $model): void;
}
