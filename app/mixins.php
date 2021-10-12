<?php declare(strict_types = 1);

/**
 * Mixins for Laravel's classes.
 */

namespace App;

use App\Mixins\EloquentBuilderMixin;
use App\Mixins\QueryBuilderMixin;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

use function class_exists;

if (class_exists(QueryBuilder::class)) {
    QueryBuilder::mixin(new QueryBuilderMixin());
}

if (class_exists(EloquentBuilder::class)) {
    EloquentBuilder::mixin(new EloquentBuilderMixin());
}
