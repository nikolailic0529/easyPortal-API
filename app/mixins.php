<?php declare(strict_types = 1);

/**
 * Mixins for Laravel's classes.
 */

namespace App;

use App\Mixins\EloquentBuilderMixin;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

use function class_exists;

if (class_exists(EloquentBuilder::class)) {
    EloquentBuilder::mixin(new EloquentBuilderMixin());
}
