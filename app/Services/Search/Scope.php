<?php declare(strict_types = 1);

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Model;

interface Scope {
    public function applyForSearch(Builder $builder, Model $model): void;
}